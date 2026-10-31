"""Synthetic-only tests; never contacts a game or CGM backend."""
import json
import unittest
from cgm_membership import MembershipClient, MembershipDecision
from cgm_membership_gate import require_active, CgmMembershipError
from cgm_attempts import MembershipAttemptCoordinator

class TestCgmPreflight(unittest.TestCase):
    def reply(self, body, timeout, status='ACTIVE', override=None):
        d=json.loads(body)
        assert set(d)=={'email','schema','request_id'} and timeout==8.0
        out={'schema':d['schema'],'request_id':d['request_id'],'status':status,
             'server_time':100000,'membership_expires_at':100060,'ttl_ms':5000}
        if override:out.update(override)
        return 200,'application/json',json.dumps(out).encode()

    def check(self, transport):
        return MembershipClient(transport=transport, clock=lambda:100000)

    def test_eligible(self):
        decision=require_active('f@example.invalid',client=self.check(self.reply))
        self.assertTrue(decision.eligible)
        self.assertFalse(hasattr(decision,'provider_session'))

    def test_inactive(self):
        with self.assertRaises(CgmMembershipError):
            require_active('f@example.invalid',client=self.check(lambda b,t:self.reply(b,t,'INACTIVE')))

    def test_clock_skew(self):
        client=MembershipClient(transport=self.reply, clock=lambda:100600)
        with self.assertRaises(CgmMembershipError):require_active('f@example.invalid',client=client)

    def test_claimed_email_not_a_game_token(self):
        sent=[]
        def trans(b,t):
            sent.append(json.loads(b))
            return self.reply(b,t)
        require_active('f@example.invalid',client=self.check(trans))
        self.assertEqual(set(sent[0]),{'schema','request_id','email'})

    def test_wrong_nonce(self):
        with self.assertRaises(CgmMembershipError):
            require_active('f@example.invalid',client=self.check(lambda b,t:self.reply(b,t,override={'request_id':'different'})))

    def test_failed_network(self):
        def fail(b,t):raise OSError('fictional-secret')
        with self.assertRaises(CgmMembershipError) as e:
            require_active('f@example.invalid',client=self.check(fail))
        self.assertNotIn('secret',str(e.exception))

    def test_one_attempt_cancels_stale(self):
        c=MembershipAttemptCoordinator()
        a=c.begin('f@example.invalid')
        c.cancel()
        self.assertEqual(c.finish(a,MembershipDecision('ACTIVE',a.request_id,100060),'f@example.invalid'),'STALE')

    def test_client_claim_not_account_ownership(self):
        d=require_active('f@example.invalid',client=self.check(self.reply))
        self.assertFalse(hasattr(d,'official_login_authenticated'))

if __name__=='__main__':unittest.main(verbosity=2)
