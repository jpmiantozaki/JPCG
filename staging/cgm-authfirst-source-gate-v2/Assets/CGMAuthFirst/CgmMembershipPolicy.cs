// Shared pure C# membership policy for Unity and portable offline tests.
// CGM eligibility ONLY; never accepts/creates PlayPark identity or sessions.
using System;

namespace CGM.AuthFirst
{
    [Serializable]
    public sealed class CgmMembershipReply
    {
        public string schema;
        public string request_id;
        public string status;
        public long server_time;
        public long membership_expires_at;
        public long ttl_ms;
    }

    // Pure response decision rules; not an official account-authentication decision.
    public static class CgmMembershipPolicy
    {
        public const string Schema = "cgm-email-membership-v1";
        public const string MembershipUrl = "https://jpcg.onrender.com/authfirst.php";

        public static bool AcceptActive(CgmMembershipReply reply, string expectedRequestId, long utcNow)
        {
            if (reply == null || string.IsNullOrEmpty(expectedRequestId)) return false;
            if (reply.schema != Schema || reply.request_id != expectedRequestId) return false;
            if (reply.status != "ACTIVE") return false;
            if (utcNow <= 0 || reply.server_time < utcNow - 300 || reply.server_time > utcNow + 300) return false;
            if (reply.membership_expires_at <= reply.server_time || reply.membership_expires_at <= utcNow) return false;
            if (reply.membership_expires_at > 253402300799L) return false;
            if (reply.ttl_ms <= 0 || reply.ttl_ms > 5000) return false;
            if (reply.ttl_ms > (reply.membership_expires_at - reply.server_time) * 1000L) return false;
            return true;
        }

        public static bool ValidEmailCandidate(string value)
        {
            // Preserve the original user's value; never silently alter the credential field.
            if (string.IsNullOrEmpty(value) || value.Length > 254 || value != value.Trim()) return false;
            int at = value.IndexOf('@');
            if (at < 1 || at != value.LastIndexOf('@') || at >= value.Length - 3) return false;
            if (value.IndexOf('.', at + 2) < 0) return false;
            foreach (char c in value) if (char.IsWhiteSpace(c) || char.IsControl(c)) return false;
            return true;
        }
    }

}
