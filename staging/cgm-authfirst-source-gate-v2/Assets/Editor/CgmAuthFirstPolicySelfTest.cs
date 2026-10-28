// New phase ONLY: deterministic response-contract checks, no network or game login.
#if UNITY_EDITOR
using System;
using CGM.AuthFirst;
using UnityEditor;
using UnityEngine;

public static class CgmAuthFirstPolicySelfTest
{
    [MenuItem("CGM/Auth-First Source Gate/Run new reply-policy selftests (Synthetic)")]
    public static void Run()
    {
        const long now = 1793184000L; // Fixture timestamp, not device clock.
        int passed = 0;
        int total = 0;
        Action<string, bool> assert = (name, okay) => {
            total++;
            if (okay) { passed++; Debug.Log("PASS CGM SOURCE CONTRACT: " + name); }
            else Debug.LogError("FAIL CGM SOURCE CONTRACT: " + name);
        };
        Func<CgmMembershipReply> fresh = () => new CgmMembershipReply {
            schema = CgmMembershipPolicy.Schema,
            request_id = "fixed-request-id",
            status = "ACTIVE",
            server_time = now,
            membership_expires_at = now + 3600,
            ttl_ms = 5000
        };
        var r = fresh(); assert("fresh ACTIVE", CgmMembershipPolicy.AcceptActive(r, r.request_id, now));
        assert("null", !CgmMembershipPolicy.AcceptActive(null, "fixed-request-id", now));
        r = fresh(); r.schema = "unknown"; assert("schema mismatch", !CgmMembershipPolicy.AcceptActive(r, "fixed-request-id", now));
        r = fresh(); assert("request ID mismatch", !CgmMembershipPolicy.AcceptActive(r, "other-request", now));
        r = fresh(); r.status = "INACTIVE"; assert("INACTIVE", !CgmMembershipPolicy.AcceptActive(r, "fixed-request-id", now));
        r = fresh(); r.status = "ERROR"; assert("ERROR", !CgmMembershipPolicy.AcceptActive(r, "fixed-request-id", now));
        r = fresh(); r.status = "EXPIRED"; assert("EXPIRED", !CgmMembershipPolicy.AcceptActive(r, "fixed-request-id", now));
        r = fresh(); r.membership_expires_at = now; assert("expiry <= now", !CgmMembershipPolicy.AcceptActive(r, "fixed-request-id", now));
        r = fresh(); r.membership_expires_at = now - 1; assert("expiry past", !CgmMembershipPolicy.AcceptActive(r, "fixed-request-id", now));
        r = fresh(); r.ttl_ms = 0; assert("zero TTL", !CgmMembershipPolicy.AcceptActive(r, "fixed-request-id", now));
        r = fresh(); r.ttl_ms = 5001; assert("oversized TTL", !CgmMembershipPolicy.AcceptActive(r, "fixed-request-id", now));
        r = fresh(); r.server_time = now - 301; assert("stale server timestamp", !CgmMembershipPolicy.AcceptActive(r, "fixed-request-id", now));
        r = fresh(); r.server_time = now + 301; assert("future server timestamp", !CgmMembershipPolicy.AcceptActive(r, "fixed-request-id", now));
        r = fresh(); r.membership_expires_at = 253402300800L; assert("unbounded expiry", !CgmMembershipPolicy.AcceptActive(r, "fixed-request-id", now));
        assert("valid fixture email", CgmMembershipPolicy.ValidEmailCandidate("member@example.com"));
        assert("missing email", !CgmMembershipPolicy.ValidEmailCandidate(""));
        assert("whitespace", !CgmMembershipPolicy.ValidEmailCandidate(" member@example.com"));
        assert("multiple at", !CgmMembershipPolicy.ValidEmailCandidate("a@@example.com"));
        Debug.Log("CGM NEW SOURCE POLICY SELFTEST: " + passed + "/" + total +
            " (SYNTHETIC ONLY; NO OFFICIAL AUTH)");
    }
}
#endif
