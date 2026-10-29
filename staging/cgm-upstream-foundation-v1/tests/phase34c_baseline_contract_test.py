#!/usr/bin/env python3
"""Synthetic metadata mutations plus privacy-safe archived fixture checks."""
from __future__ import annotations
import copy
import json
import sys
import unittest
from pathlib import Path

ROOT=Path(__file__).resolve().parent.parent
sys.path.insert(0,str(ROOT/"research"))
from phase34c_baseline_contract import classify

EVIDENCE=ROOT/"evidence"

class Phase34CContractTests(unittest.TestCase):
    @staticmethod
    def fixture(name="ANDROID_NOX_RECORDED_SAFE.json"):
        return json.loads((EVIDENCE/name).read_text(encoding="utf-8"))

    def test_android_archived_order_not_session(self):
        result=classify(self.fixture())
        self.assertTrue(result["original_client_order_observed"])
        self.assertFalse(result["independent_cgm_mobile_session_proven"])

    def test_pc_archived_order_not_session(self):
        result=classify(self.fixture("PC_RECORDED_SAFE.json"))
        self.assertTrue(result["original_client_order_observed"])
        self.assertFalse(result["official_account_authenticated_by_this_report"])

    def test_android_total_frame_count(self):
        self.assertEqual(self.fixture()["total_frames"],52)

    def test_pc_total_frame_count(self):
        self.assertEqual(self.fixture("PC_RECORDED_SAFE.json")["total_frames"],255)

    def test_android_timing_scales_ns(self):
        t=self.fixture()["checkpoint_relative_seconds"]
        self.assertLess(t["GARDEN_LOAD_ZERO_RESULT"],9)

    def test_pc_stale_probe_candidates_explicit(self):
        self.assertEqual(self.fixture("PC_RECORDED_SAFE.json")["stale_one_byte_overlap_candidates"],5)

    def test_no_process_identity_claim(self):
        self.assertFalse(classify(self.fixture())["emitting_app_cryptographically_verified"])

    def test_reject_claimed_mobile_issuer(self):
        d=self.fixture()
        d["new_cgm_mobile_session_issued"]=True
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_claimed_provider_account(self):
        d=self.fixture()
        d["official_provider_account_bound"]=True
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_claimed_entitlement(self):
        d=self.fixture()
        d["cgmonly_vip_accepted"]=True
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_reversed_garden_response_order(self):
        d=self.fixture()
        t=d["checkpoint_relative_seconds"]
        t["GARDEN_LOAD_ZERO_RESULT"]=t["GARDEN_LOGIN_ZERO_RESULT"]-0.01
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_timestamp_missing(self):
        d=self.fixture()
        del d["checkpoint_relative_seconds"]["GAME_ZERO_RESULT"]
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_capture_gap(self):
        d=self.fixture();d["tcp_sequence_gaps"]=1
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_multi_socket(self):
        d=self.fixture();d["single_tcp_connection_per_service"]=False
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_account_identifier_leak(self):
        d=self.fixture();d["email"]="fictional@example.invalid"
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_raw_packet_leak(self):
        d=self.fixture();d["packet_data"]="001122"
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_bad_capture_provenance(self):
        d=self.fixture();d["archive_provenance"]="LIVE_VERIFIED_PROVIDER"
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_wrong_frame_total(self):
        d=self.fixture();d["total_frames"]+=1
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_missing_garden_frames(self):
        d=self.fixture();d["frames"]["20840_S2C"]=0
        d["total_frames"]=sum(d["frames"].values())
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_untrusted_timestamp_type(self):
        d=self.fixture();d["checkpoint_relative_seconds"]["LOGIN_ZERO_RESULT"]="0.09"
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_negative_overlap_count(self):
        d=self.fixture();d["stale_one_byte_overlap_candidates"]=-1
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_reject_zero_interval_fake_progress(self):
        d=self.fixture()
        d["checkpoint_relative_seconds"]["GARDEN_LOAD_ZERO_RESULT"]=d["checkpoint_relative_seconds"]["GARDEN_LOGIN_ZERO_RESULT"]
        self.assertFalse(classify(d)["original_client_order_observed"])

    def test_negative_outputs_never_claim_authorization(self):
        for d in (None,[],{},{"fake":"user"},self.fixture()):
            r=classify(d)
            self.assertFalse(r["independent_cgm_mobile_session_proven"])
            self.assertFalse(r["garden_authorized_for_new_mobile_session"])

if __name__=="__main__":unittest.main(verbosity=2)
