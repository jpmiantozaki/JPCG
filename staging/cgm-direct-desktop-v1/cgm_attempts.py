"""Single-flight, fail-closed UI attempt ownership; no game/session operations."""
from __future__ import annotations
from dataclasses import dataclass
import secrets
from typing import Optional
from cgm_membership import MembershipDecision, valid_email_candidate

@dataclass(frozen=True)
class MembershipAttempt:
    generation: int
    email: str
    request_id: str

class MembershipAttemptCoordinator:
    def __init__(self):
        self._generation = 0
        self._pending: Optional[MembershipAttempt] = None

    @property
    def is_pending(self):
        return self._pending is not None

    def begin(self, email: str) -> Optional[MembershipAttempt]:
        if self.is_pending:
            return None
        if not valid_email_candidate(email):
            raise ValueError("Enter a valid CGM membership email")
        self._generation += 1
        attempt = MembershipAttempt(self._generation, email, secrets.token_hex(16))
        self._pending = attempt
        return attempt

    def cancel(self):
        self._generation += 1
        self._pending = None

    def finish(self, attempt: MembershipAttempt, result: MembershipDecision,
               current_email: str) -> str:
        if self._pending != attempt or attempt.generation != self._generation:
            return "STALE"
        self._pending = None
        if current_email != attempt.email or result.request_id != attempt.request_id:
            return "STALE"
        return "ACTIVE" if result.eligible else result.status
