<?php
declare(strict_types=1);
namespace CGM\Phase40CC;

/**
 * Safe JSON metadata check for a previously-authorized response. No JSON
 * string, packet, session, message or credential is returned or logged.
 * A positive SHAPE is NOT an official login or Garden authorization.
 */
final class ResponseContract
{
    private static function typeOf(object $data, string $name): string
    {
        if (!property_exists($data, $name)) return 'missing';
        $v = $data->$name;
        return match (true) {
            $v === null => 'null',
            is_int($v) => 'integer',
            is_float($v) => 'float',
            is_string($v) => 'string',
            is_bool($v) => 'boolean',
            is_array($v) => 'array',
            is_object($v) => 'object',
            default => 'other',
        };
    }

    /** @return array<string,string|bool|int|null> */
    public static function inspect(string $json, ?int $observedRequestStage = null): array
    {
        $base = ['scope' => 'RESPONSE_SHAPE_ONLY', 'officially_authenticated' => false,
            'game_session_issued' => false, 'garden_authorized' => false];
        if ($json === '' || strlen($json) > 65536) {
            return $base + ['classification' => 'INVALID_RESPONSE_SIZE'];
        }
        try {
            $data = json_decode($json, false, 32, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return $base + ['classification' => 'INVALID_RESPONSE_JSON'];
        }
        if (!is_object($data)) return $base + ['classification' => 'INVALID_RESPONSE_ROOT'];
        $serverType = property_exists($data, 'servertype') ? $data->servertype : null;
        $integerType = is_int($serverType) && ($serverType === 0 || $serverType === 1);
        $expiry = property_exists($data, 'vip_expiry') ? $data->vip_expiry : null;
        $meta = [
            'servertype_present' => property_exists($data, 'servertype'),
            'servertype_json_type' => self::typeOf($data, 'servertype'),
            'servertype_integer_route' => $integerType ? $serverType : null,
            'vip_expiry_present' => property_exists($data, 'vip_expiry'),
            'vip_expiry_json_type' => self::typeOf($data, 'vip_expiry'),
            'stage_route_matches_hypothesis' => $integerType && $observedRequestStage !== null
                ? $serverType === $observedRequestStage : false,
        ];
        if (!property_exists($data, 'error') || !is_bool($data->error)) {
            return $base + ['classification' => 'INVALID_ERROR_TYPE'] + $meta;
        }
        if ($data->error === true) {
            return $base + ['classification' => 'UPSTREAM_REJECTION_SHAPE'] + $meta;
        }
        // All checks below are only about PHP JSON shape; they do not infer
        // Unity coercion or a legitimate server's acceptance of packet bytes.
        if (!$integerType) {
            return $base + ['classification' => 'POSITIVE_UNRESOLVED_SERVERTYPE'] + $meta;
        }
        $hex = property_exists($data, 'data') && is_string($data->data) ? $data->data : '';
        if ($hex === '' || strlen($hex) % 2 !== 0 || !ctype_xdigit($hex)) {
            return $base + ['classification' => 'POSITIVE_INVALID_PACKET_SHAPE'] + $meta;
        }
        if (!property_exists($data, 'session') || !is_string($data->session)
            || $data->session === '') {
            return $base + ['classification' => 'POSITIVE_INVALID_RELAY_STATE_SHAPE'] + $meta;
        }
        return $base + ['classification' => 'POSITIVE_ENVELOPE_SHAPE_UNVERIFIED'] + $meta;
    }
}
