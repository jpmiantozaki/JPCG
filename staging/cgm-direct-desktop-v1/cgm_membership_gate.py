"""Adapter reusing the previously tested Phase2.8.6 JPCG email membership preflight.
This checks only membership. It never verifies PlayPark account ownership.
"""
from __future__ import annotations
from cgm_membership import MembershipClient

class CgmMembershipError(Exception):
    pass

def require_active(email: str, *, client: MembershipClient | None = None):
    decision = (client or MembershipClient()).check(email)
    if not decision.eligible:
        raise CgmMembershipError(decision.detail or 'CGM membership unavailable or inactive.')
    return decision
