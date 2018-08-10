<?php
namespace App\Library\Services;

use Illuminate\Http\Request;
//use App\Model\User;
use App\Model\Audit as AuditTable;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\IpAddressResolver;
use OwenIt\Auditing\Contracts\UrlResolver;
use OwenIt\Auditing\Contracts\UserAgentResolver;
use OwenIt\Auditing\Contracts\UserResolver;
use OwenIt\Auditing\Exceptions\AuditingException;

class Audit
{

    public static $user_id;
    public static $audit_id;
    public static $comment;
    public static $one_time = FALSE;
    public static $no_user = FALSE;

    public static function user($id)
    {
        // set the user id
        static::$user_id = $id;
        return new static;
    }

    public static function record($event, $type_object)
    {
        // for now
        $old_values = [];
        $new_values = [];

        if(is_null($user_id = self::$user_id)) {

            $user_id = request()->user()->id;
        }

        // if one_time is true then dont add a new record if a similar
        // record exists
        if(static::$one_time) {
            if(AuditTable::where('user_type','App\Model\User')
                        ->where('event', $event)
                        ->where('auditable_type', get_class($type_object))
                        ->where('auditable_id', $type_object->id)
                        ->first()) {
                return;
            }
        }

        // store
        static::$audit_id = AuditTable::create([
            'user_type' => (static::$no_user ? NULL : 'App\Model\User'),
            'user_id' => (static::$no_user ? NULL : $user_id),
            'event' => $event,
            'auditable_type' => get_class($type_object),
            'auditable_id' => $type_object->id,
            'old_values' =>  json_encode($old_values),
            'new_values' => json_encode($new_values),
            'url'                => static::resolveUrl(),
            'ip_address'         => static::resolveIpAddress(),
            'user_agent'         => static::resolveUserAgent(),
            'comments' => static::$comment           
        ]);

        return new static;
    }

    public static function comment($comment)
    {
        static::$comment = $comment;
        return new static;
    }

    public static function oneTime()
    {
        static::$one_time = TRUE;
        return new static;
    }

    public static function noUser()
    {
        static::$no_user = TRUE;
        return new static;
    }

    /**
     * Resolve the URL.
     *
     * @throws AuditingException
     *
     * @return string
     */
    public static function resolveUrl(): string
    {
        $urlResolver = \Config::get('audit.resolver.url');

        if (is_subclass_of($urlResolver, UrlResolver::class)) {
            return call_user_func([$urlResolver, 'resolve']);
        }

        throw new AuditingException('Invalid UrlResolver implementation');
    }

    /**
     * Resolve the IP Address.
     *
     * @throws AuditingException
     *
     * @return string
     */
    public static function resolveIpAddress(): string
    {
        $ipAddressResolver = \Config::get('audit.resolver.ip_address');

        if (is_subclass_of($ipAddressResolver, IpAddressResolver::class)) {
            return call_user_func([$ipAddressResolver, 'resolve']);
        }

        throw new AuditingException('Invalid IpAddressResolver implementation');
    }

    /**
     * Resolve the User Agent.
     *
     * @throws AuditingException
     *
     * @return string|null
     */
    public static function resolveUserAgent()
    {
        $userAgentResolver = \Config::get('audit.resolver.user_agent');

        if (is_subclass_of($userAgentResolver, UserAgentResolver::class)) {
            return call_user_func([$userAgentResolver, 'resolve']);
        }

        throw new AuditingException('Invalid UserAgentResolver implementation');
    }

}