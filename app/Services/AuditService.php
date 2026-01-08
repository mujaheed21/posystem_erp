<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;

class AuditService
{
    public static function log(
        string $action,
        ?string $module = null,
        ?string $auditableType = null,
        ?int $auditableId = null,
        array $metadata = []
    ): void {
        DB::table('audit_logs')->insert([
            'business_id'    => Auth::user()->business_id ?? null,
            'user_id'        => Auth::id(),
            'action'         => $action,
            'module'         => $module,
            'auditable_type' => $auditableType,
            'auditable_id'   => $auditableId,
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
            'metadata'       => json_encode($metadata),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }
}
