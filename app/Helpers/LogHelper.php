<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class LogHelper
{
    public static function log($action, $model, $modelId = null, $details = null)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => $action,
            'model'   => $model,
            'model_id' => (string) $modelId,
            'details' => $details,
        ]);
    }
}
