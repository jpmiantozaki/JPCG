// Portable deterministic tests against the ACTUAL CGM Unity C# policy file.
// No network, Unity, PlayPark credentials, or login/game session.
using System;
using CGM.AuthFirst;

public static class Program
{
    static int passed, total;
    const long Now = 1793184000L;
    const string Rid = "test-request-01";
    static CgmMembershipReply Active() => new CgmMembershipReply {
        schema = CgmMembershipPolicy.Schema,
        request_id = Rid,
        status = "ACTIVE",
        server_time = Now,
        membership_expires_at = Now + 3600,
        ttl_ms = 5000
    };
    static void Check(string name, bool ok) {
        total++; if(ok) { passed++; Console.WriteLine("PASS " + name); }
        else Console.Error.WriteLine("FAIL " + name);
    }
    static bool Accept(CgmMembershipReply r, long now=Now) =>
        CgmMembershipPolicy.AcceptActive(r, Rid, now);
    public static int Main() {
        var r=Active(); Check("fresh ACTIVE", Accept(r));
        Check("null", !Accept(null));
        Check("empty ID", !CgmMembershipPolicy.AcceptActive(r, "", Now));
        Check("null ID", !CgmMembershipPolicy.AcceptActive(r, null, Now));
        Check("wrong expected ID", !CgmMembershipPolicy.AcceptActive(r, "other", Now));
        r=Active(); r.request_id="other"; Check("cross-request reply", !Accept(r));
        r=Active(); r.schema="wrong"; Check("wrong schema", !Accept(r));
        r=Active(); r.status="INACTIVE"; Check("inactive", !Accept(r));
        r=Active(); r.status="ERROR"; Check("backend error", !Accept(r));
        r=Active(); r.status="EXPIRED"; Check("expired status", !Accept(r));
        r=Active(); r.status="active"; Check("wrong status case", !Accept(r));
        r=Active(); r.status=""; Check("missing status", !Accept(r));
        r=Active(); r.server_time=Now-300; Check("lower clock boundary", Accept(r));
        r=Active(); r.server_time=Now+300; Check("upper clock boundary", Accept(r));
        r=Active(); r.server_time=Now-301; Check("stale server time", !Accept(r));
        r=Active(); r.server_time=Now+301; Check("future server time", !Accept(r));
        r=Active(); r.membership_expires_at=Now; Check("expiry at now", !Accept(r));
        r=Active(); r.membership_expires_at=Now-1; Check("expired", !Accept(r));
        r=Active(); r.membership_expires_at=Now+1; r.ttl_ms=1000;
        Check("1s exact TTL", Accept(r));
        r=Active(); r.membership_expires_at=Now+1; r.ttl_ms=1001;
        Check("TTL exceeds membership window", !Accept(r));
        r=Active(); r.ttl_ms=0; Check("zero TTL", !Accept(r));
        r=Active(); r.ttl_ms=-1; Check("negative TTL", !Accept(r));
        r=Active(); r.ttl_ms=5001; Check("over-limit TTL", !Accept(r));
        r=Active(); r.membership_expires_at=253402300800L; Check("unbounded expiry", !Accept(r));
        r=Active(); r.membership_expires_at=Now+1; r.server_time=Now+2;
        Check("expiry before server time", !Accept(r));
        Check("invalid local now", !CgmMembershipPolicy.AcceptActive(Active(), Rid, 0));
        Check("valid email", CgmMembershipPolicy.ValidEmailCandidate("member@example.com"));
        Check("blank email", !CgmMembershipPolicy.ValidEmailCandidate(""));
        Check("null email", !CgmMembershipPolicy.ValidEmailCandidate(null));
        Check("surrounding whitespace", !CgmMembershipPolicy.ValidEmailCandidate(" member@example.com "));
        Check("multiple at symbols", !CgmMembershipPolicy.ValidEmailCandidate("a@@example.com"));
        Check("missing at", !CgmMembershipPolicy.ValidEmailCandidate("not-an-email"));
        Check("missing domain dot", !CgmMembershipPolicy.ValidEmailCandidate("member@example"));
        Check("control characters", !CgmMembershipPolicy.ValidEmailCandidate("a\nb@example.com"));
        Check("overlong email", !CgmMembershipPolicy.ValidEmailCandidate(new string('a',250)+"@e.com"));
        Check("HTTPS membership URL", CgmMembershipPolicy.MembershipUrl ==
          "https://jpcg.onrender.com/authfirst.php");
        Console.WriteLine("CGM PURE C# POLICY RUNTIME TESTS: "+passed+"/"+total+
          " (NO UNITY/APK/OFFICIAL AUTH)");
        return passed==total ? 0 : 1;
    }
}
