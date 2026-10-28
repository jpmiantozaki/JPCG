-- Optional scheduled maintenance; remove only attempts expired more than one day.
DELETE FROM cgm_upstream_admission_attempts
WHERE valid_until < CAST(EXTRACT(EPOCH FROM clock_timestamp()) AS BIGINT) - 86400;
