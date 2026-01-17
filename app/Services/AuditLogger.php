<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class AuditLogger
{
    protected $allowedMetadataKeys = [
        'ttl_minutes', 'expires_at', 'limit', 'usage', 'type', 'reason', 
        'new_status', 'tenant_id', 'support_session_id', 'candidate_tenant_id',
        'path', 'pending_invites_counted'
    ];

    /**
     * Log an audit event safely.
     */
    public function log(string $eventKey, array $context = [], string $severity = 'info'): void
    {
        if (! config('audit.enabled', true)) {
            return;
        }

        try {
            // 1. Resolve Context
            $user = auth()->user();
            
            // Context Resolution Polish
            // If request is /admin/* or /support/access/*, DO NOT trust session('current_tenant_id') blindly.
            // Only use what is passed in context or if explicitly set in app service.
            $requestRoute = Request::route() ? Request::route()->getName() : null;
            $isPlatformRoute = $requestRoute && (str_starts_with($requestRoute, 'admin.') || str_starts_with($requestRoute, 'support.'));
            
            if ($isPlatformRoute) {
                 $tenantId = $context['tenant_id'] ?? null;
            } else {
                 $tenantId = session('current_tenant_id') ?? $context['tenant_id'] ?? null;
            }
            
            // Resolve Actor Type
            $actorType = 'system';
            if ($user) {
                $actorType = $user->is_admin ? 'platform_admin' : 'tenant_user';
            }

            // 2. Request Capture (Safe)
            $requestRoute = Request::route() ? Request::route()->getName() : null;
            $requestMethod = Request::method();
            $requestIp = Request::ip();
            $userAgent = Request::header('User-Agent');

            // 3. PII & Metadata Sanitization
            $safeMetadata = $this->sanitizeMetadata($context);

            // 4. Create Log
            AuditLog::create([
                'event_key' => $eventKey,
                'severity' => $severity,
                'actor_user_id' => $user?->id,
                'actor_type' => $actorType,
                'tenant_id' => $tenantId,
                'account_id' => $context['account_id'] ?? null,
                'support_session_id' => session('support_session_id') ?? $context['support_session_id'] ?? null,
                'route' => $requestRoute, // Prefer route name
                'method' => substr($requestMethod, 0, 8),
                'ip_trunc' => $this->anonymizeIp($requestIp),
                'user_agent_trunc' => substr($userAgent, 0, 120),
                'metadata' => $safeMetadata,
                'occurred_at' => now(),
            ]);

        } catch (\Throwable $e) {
            // Failure-safe: Do not break the app flow.
            Log::error("Audit Log Failure: {$e->getMessage()}", [
                'event' => $eventKey,
                'ctx' => $context
            ]);
        }
    }

    protected function sanitizeMetadata(array $data): array
    {
        $safe = [];
        foreach ($data as $key => $value) {
            // DateTime Object Handling
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            }
            // Allowlist check (optional, or just sanitize everything?)
            // User requested allowlist.
            if (! in_array($key, $this->allowedMetadataKeys)) {
                // If extra specific keys needed, add to allowlist.
                // For now, strict filter or allow with sanitization?
                // "Metadata allowlist mantığıyla yazılsın" -> Strict is safer.
                continue; 
            }

            if (is_string($value)) {
                // Email-like Redaction
                if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $value)) {
                    $value = $this->maskEmail($value);
                }
                
                // Token-like Redaction (long random strings, >40 chars)
                if (strlen($value) > 40 && !str_contains($value, ' ')) {
                   $value = '[REDACTED_TOKEN]';
                }

                // Path sanitization if key is 'path'
                if ($key === 'path') {
                    $value = $this->sanitizePath($value);
                }
            }

            $safe[$key] = $value;
        }
        return $safe;
    }

    public function sanitizePath(string $path): string
    {
        // Remove Query String
        $path = strtok($path, '?');

        // Initial slash
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // Mask Tokens in Path segments (e.g. /invite/TOKEN -> /invite/[REDACTED])
        // Heuristic: specific segments or long mixed-case strings
        $segments = explode('/', $path);
        foreach ($segments as &$segment) {
            // Basic heuristic for token: > 20 chars, alphanumeric
            if (strlen($segment) > 20 && preg_match('/^[a-zA-Z0-9]+$/', $segment)) {
                $segment = '[REDACTED]';
            }
        }
        
        $path = implode('/', $segments);

        // Truncate
        return substr($path, 0, 120);
    }

    protected function maskEmail(string $email): string
    {
        // Simple mask: a***@d***.com
        return preg_replace_callback('/^(.{1})(.*)(@.{1})(.*)(\..{2,})/', function ($matches) {
            return $matches[1] . '***' . $matches[3] . '***' . $matches[5];
        }, $email) ?? '***@***.com';
    }

    protected function anonymizeIp(?string $ip): ?string
    {
        if (!$ip) return null;
        // Simple last octet zeroing for IPv4
        return preg_replace('/(\d+)\.(\d+)\.(\d+)\.(\d+)/', '$1.$2.$3.0', $ip);
    }
}
