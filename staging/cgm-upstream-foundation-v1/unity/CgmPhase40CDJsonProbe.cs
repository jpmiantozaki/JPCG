using System;
using UnityEditor;
using UnityEngine;

// Unity Editor-only, OFFLINE. Original APK and provider endpoints are never used.
// This is a synthetic type-coercion probe, NOT a valid game login/session reply.
public static class CgmPhase40CDJsonProbe
{
    [Serializable]
    private sealed class RelayResponseFixture
    {
        public bool error;
        public string message;
        public string data;
        public int servertype;
        public string session;
        public uint vip_expiry;
    }

    private sealed class SyntheticCase
    {
        public readonly string Label;
        public readonly string Json;
        public SyntheticCase(string label, string json)
        {
            Label = label;
            Json = json;
        }
    }

    // The exact original serialized class field names and their types are used.
    // Values are fabricated. Do NOT substitute actual upstream JSON/credentials.
    private static readonly SyntheticCase[] Cases =
    {
        new SyntheticCase("missing_type",       "{\"error\":false}"),
        new SyntheticCase("integer_zero",       "{\"error\":false,\"servertype\":0}"),
        new SyntheticCase("integer_one",        "{\"error\":false,\"servertype\":1}"),
        new SyntheticCase("string_zero",        "{\"error\":false,\"servertype\":\"0\"}"),
        new SyntheticCase("string_one",         "{\"error\":false,\"servertype\":\"1\"}"),
        new SyntheticCase("decimal_one",        "{\"error\":false,\"servertype\":1.0}"),
        new SyntheticCase("exponent_one",       "{\"error\":false,\"servertype\":1e0}"),
        new SyntheticCase("explicit_null",      "{\"error\":false,\"servertype\":null}"),
        new SyntheticCase("boolean_true",       "{\"error\":false,\"servertype\":true}"),
        new SyntheticCase("negative_integer",   "{\"error\":false,\"servertype\":-1}"),
        new SyntheticCase("integer_two",        "{\"error\":false,\"servertype\":2}"),
        new SyntheticCase("out_of_byte_range",  "{\"error\":false,\"servertype\":256}"),
        new SyntheticCase("duplicate_zero_one", "{\"error\":false,\"servertype\":0,\"servertype\":1}"),
        new SyntheticCase("duplicate_one_zero", "{\"error\":false,\"servertype\":1,\"servertype\":0}"),
        new SyntheticCase("expiry_missing",     "{\"error\":false,\"servertype\":1}"),
        new SyntheticCase("expiry_integer",     "{\"error\":false,\"servertype\":1,\"vip_expiry\":123}"),
        new SyntheticCase("expiry_string",      "{\"error\":false,\"servertype\":1,\"vip_expiry\":\"123\"}"),
        new SyntheticCase("expiry_decimal",     "{\"error\":false,\"servertype\":1,\"vip_expiry\":123.0}"),
        new SyntheticCase("expiry_null",        "{\"error\":false,\"servertype\":1,\"vip_expiry\":null}"),
        new SyntheticCase("expiry_negative",    "{\"error\":false,\"servertype\":1,\"vip_expiry\":-1}"),
        new SyntheticCase("logical_error_true", "{\"error\":true,\"servertype\":0}"),
        new SyntheticCase("logical_error_text", "{\"error\":\"true\",\"servertype\":0}"),
    };

    [MenuItem("Tools/CGM/Phase 40CD/Run Offline JSON Probe")]
    public static void Run()
    {
        string unityVersion = Application.unityVersion;
        Debug.Log("CGM40CD_PROBE_BEGIN unity=" + unityVersion + " count=" + Cases.Length
                  + " network=none original_apk=untouched");
        int genericFailures = 0;
        int nongenericFailures = 0;
        for (int i = 0; i < Cases.Length; i++)
        {
            // Generic and non-generic calls exercise both public overloads.
            // Parsing behavior is OBSERVED, not pre-guessed from PHP's decoder.
            string a = InspectGeneric(Cases[i].Json);
            string b = InspectNonGeneric(Cases[i].Json);
            if (a.StartsWith("exception:", StringComparison.Ordinal)) genericFailures++;
            if (b.StartsWith("exception:", StringComparison.Ordinal)) nongenericFailures++;
            Debug.Log("CGM40CD_CASE label=" + Cases[i].Label
                      + " generic=" + a + " nongeneric=" + b);
        }
        Debug.Log("CGM40CD_PROBE_END unity=" + unityVersion
                  + " cases=" + Cases.Length
                  + " generic_exceptions=" + genericFailures
                  + " nongeneric_exceptions=" + nongenericFailures
                  + " official_auth=NOT_TESTED garden=NOT_TESTED");
    }

    private static string InspectGeneric(string syntheticJson)
    {
        try
        {
            RelayResponseFixture parsed = JsonUtility.FromJson<RelayResponseFixture>(syntheticJson);
            return Result(parsed);
        }
        catch (Exception ex)
        {
            // Exception class name only. No JSON, token, stack or arbitrary message.
            return "exception:" + ex.GetType().Name;
        }
    }

    private static string InspectNonGeneric(string syntheticJson)
    {
        try
        {
            RelayResponseFixture parsed =
                JsonUtility.FromJson(syntheticJson, typeof(RelayResponseFixture)) as RelayResponseFixture;
            return Result(parsed);
        }
        catch (Exception ex)
        {
            return "exception:" + ex.GetType().Name;
        }
    }

    private static string Result(RelayResponseFixture parsed)
    {
        if (parsed == null) return "null_object";
        return "object_route:" + parsed.servertype
             + ",expiry:" + parsed.vip_expiry
             + ",error:" + (parsed.error ? "true" : "false");
    }
}
