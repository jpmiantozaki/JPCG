// CGM Auth-First: source-level membership precheck before an unchanged original Login() callback.
// Unity 2021.3; does not install into an existing IL2CPP APK or create official game sessions.
using System;
using System.Collections;
using UnityEngine;
using UnityEngine.Events;
using UnityEngine.Networking;
using UnityEngine.UI;

namespace CGM.AuthFirst
{
    // Run after the recovered scene's default-order LoginServerManager.Start().
    // This is fail-closed detection, not a supported APK injection mechanism.
    [DefaultExecutionOrder(30000)]
    [DisallowMultipleComponent]
    public sealed class CgmAuthFirstSubmitGate : MonoBehaviour
    {
        [Header("Existing UI references - never bind the outer yellow LOGIN button")]
        [SerializeField] private Button innerConfirmButton;
        [SerializeField] private InputField existingUsernameField;
        [SerializeField] private Text optionalMembershipStatus;

        [Header("Preserved original Login() event; populated by the Editor binder")]
        [SerializeField] private UnityEvent unchangedOriginalLogin = new UnityEvent();

        [Header("Safety: leave OFF in the AssetRipper/exported offline project")]
        [SerializeField] private bool enableOriginalLoginContinuation = false;

        private const int RequestTimeoutSeconds = 18;
        private bool wired;
        private Button.ButtonClickedEvent expectedConfirmEvent;
        private bool unexpectedWiringLogged;
        private bool subscribed;
        private GameObject originalCredentialPanel;
        private bool checking;
        private bool originalLoginBusy;
        private int generation;
        private string pendingEmail;
        private string pendingRequestId;
        private UnityWebRequest inFlight;
        private Coroutine activeRoutine;

        [Serializable]
        private sealed class EmailOnlyRequest
        {
            public string schema;
            public string request_id;
            public string email;
        }

        // Used by a reviewed source-project Editor installer only.
        public UnityEvent OriginalLoginContinuation { get { return unchangedOriginalLogin; } }

#if UNITY_EDITOR
        public void EditorConfigure(Button confirm, InputField username)
        {
            innerConfirmButton = confirm;
            existingUsernameField = username;
            enableOriginalLoginContinuation = false;
        }
#endif

        private void Start()
        {
            originalCredentialPanel = innerConfirmButton != null && innerConfirmButton.transform.parent != null
                ? innerConfirmButton.transform.parent.gameObject : null;
            wired = CheckExclusiveConfirmWiring();
            if (wired) expectedConfirmEvent = innerConfirmButton.onClick;
            if (!wired)
            {
                // Blocking the button is safer than allowing a partly wired source scene.
                LockUnsafeConfirm("CGM GATE NOT WIRED - SUBMISSION DISABLED");
                Debug.LogError("CGM SOURCE GATE: expected exclusive inner CONFIRM -> Submit and one preserved Login() callback.");
                return;
            }
            SubscribeToEmailEdits();
            SetStatus("CGM eligibility not checked");
            Debug.Log("CGM SOURCE GATE READY: JPCG HTTPS email-only; original Login continuation=" +
                (enableOriginalLoginContinuation ? "ARMED" : "TEST-ONLY"));
        }

        private bool CheckExclusiveConfirmWiring()
        {
            if (innerConfirmButton == null || existingUsernameField == null || unchangedOriginalLogin == null)
                return false;
            try
            {
                return innerConfirmButton.onClick.GetPersistentEventCount() == 1 &&
                    innerConfirmButton.onClick.GetPersistentTarget(0) == this &&
                    innerConfirmButton.onClick.GetPersistentMethodName(0) == "Submit" &&
                    unchangedOriginalLogin.GetPersistentEventCount() == 1 &&
                    unchangedOriginalLogin.GetPersistentMethodName(0) == "Login" &&
                    unchangedOriginalLogin.GetPersistentTarget(0) is LoginServerManager;
            }
            catch (Exception) { return false; }
        }

        private void SubscribeToEmailEdits()
        {
            if (!wired || subscribed || existingUsernameField == null) return;
            existingUsernameField.onValueChanged.AddListener(EmailEdited);
            subscribed = true;
        }

        private void OnEnable() { SubscribeToEmailEdits(); }

        private void SetStatus(string status)
        {
            if (optionalMembershipStatus != null) optionalMembershipStatus.text = status;
            // Status messages must not contain emails, game passwords or tokens.
            Debug.Log("CGM AUTHFIRST: " + status);
        }

        private void LockUnsafeConfirm(string reason)
        {
            wired = false;
            if (innerConfirmButton != null) innerConfirmButton.interactable = false;
            if (checking) CancelPending();
            SetStatus(reason);
            if (!unexpectedWiringLogged)
            {
                unexpectedWiringLogged = true;
                Debug.LogError("CGM SOURCE GATE: original CONFIRM wiring mismatch; blocked ordinary button interaction.");
            }
        }

        private bool IsExclusiveWiringNow()
        {
            return wired && innerConfirmButton != null &&
                ReferenceEquals(innerConfirmButton.onClick, expectedConfirmEvent) &&
                CheckExclusiveConfirmWiring();
        }

        // This is the only permitted serialized listener on the ORIGINAL inner CONFIRM button.
        public void Submit()
        {
            if (!IsExclusiveWiringNow())
            {
                LockUnsafeConfirm("CGM GATE WIRING CHANGED - SUBMISSION DISABLED");
                return;
            }
            if (!wired || checking || originalLoginBusy || existingUsernameField == null ||
                innerConfirmButton == null || !innerConfirmButton.gameObject.activeInHierarchy ||
                originalCredentialPanel == null || !originalCredentialPanel.activeInHierarchy) return;
            string candidate = existingUsernameField.text;
            if (!CgmMembershipPolicy.ValidEmailCandidate(candidate))
            {
                SetStatus("INVALID EMAIL - ORIGINAL LOGIN BLOCKED");
                return;
            }

            int epoch = ++generation;
            string requestId = Guid.NewGuid().ToString("N");
            pendingEmail = candidate;
            pendingRequestId = requestId;
            checking = true;
            SetStatus("CHECKING CGM MEMBERSHIP...");
            activeRoutine = StartCoroutine(CheckAndMaybeContinue(epoch, requestId, candidate));
        }

        private IEnumerator CheckAndMaybeContinue(int epoch, string requestId, string candidate)
        {
            var body = new EmailOnlyRequest {
                schema = CgmMembershipPolicy.Schema, request_id = requestId, email = candidate
            };
            // No password, game packet, session token, or official server address is transmitted.
            byte[] json = System.Text.Encoding.UTF8.GetBytes(JsonUtility.ToJson(body));
            using (var req = new UnityWebRequest(CgmMembershipPolicy.MembershipUrl, "POST"))
            {
                req.uploadHandler = new UploadHandlerRaw(json);
                req.downloadHandler = new DownloadHandlerBuffer();
                req.SetRequestHeader("Content-Type", "application/json");
                req.SetRequestHeader("Accept", "application/json");
                req.timeout = RequestTimeoutSeconds;
                inFlight = req;
                var operation = req.SendWebRequest();
                float start = Time.realtimeSinceStartup;
                while (!operation.isDone)
                {
                    if (!SameAttempt(epoch, requestId, candidate)) { req.Abort(); yield break; }
                    if (Time.realtimeSinceStartup - start > RequestTimeoutSeconds)
                    {
                        req.Abort();
                        Fail(epoch, "CGM TIMEOUT - ORIGINAL LOGIN BLOCKED");
                        yield break;
                    }
                    yield return null;
                }
                if (!SameAttempt(epoch, requestId, candidate)) yield break;
                if (req.result != UnityWebRequest.Result.Success || req.responseCode != 200 ||
                    req.downloadHandler == null || req.downloadHandler.text == null ||
                    req.downloadHandler.text.Length == 0 || req.downloadHandler.text.Length > 4096 ||
                    (req.GetResponseHeader("Content-Type") ?? "").IndexOf(
                        "application/json", StringComparison.OrdinalIgnoreCase) < 0)
                {
                    Fail(epoch, "CGM NETWORK ERROR - ORIGINAL LOGIN BLOCKED");
                    yield break;
                }
                CgmMembershipReply reply = null;
                try { reply = JsonUtility.FromJson<CgmMembershipReply>(req.downloadHandler.text); }
                catch (Exception) { /* Invalid JSON fails closed below. */ }
                long now = DateTimeOffset.UtcNow.ToUnixTimeSeconds();
                if (!CgmMembershipPolicy.AcceptActive(reply, requestId, now))
                {
                    Fail(epoch, "CGM INACTIVE / INVALID REPLY - ORIGINAL LOGIN BLOCKED");
                    yield break;
                }

                // Recheck UI identity and request generation immediately before continuation.
                if (!SameAttempt(epoch, requestId, candidate)) yield break;
                checking = false;
                activeRoutine = null;
                inFlight = null;
                pendingEmail = null;
                pendingRequestId = null;
                string expiryUtc = DateTimeOffset.FromUnixTimeSeconds(reply.membership_expires_at)
                    .UtcDateTime.ToString("yyyy-MM-dd HH:mm 'UTC'");
                if (!enableOriginalLoginContinuation)
                {
                    SetStatus("CGM ACTIVE (LIVE) until " + expiryUtc +
                        " - TEST ONLY; ORIGINAL LOGIN NOT CALLED");
                    yield break;
                }

                // This compile-time switch must be consciously supplied ONLY in a reviewed,
                // legitimate source integration, never in the AssetRipper stubbed project.
#if !CGM_AUTHORIZED_ORIGINAL_LOGIN_SOURCE
                SetStatus("CGM ACTIVE - ORIGINAL LOGIN BUILD NOT AUTHORIZED; BLOCKED");
                yield break;
#else
                if (!IsExclusiveWiringNow() || !SameAttemptAfterCheck(epoch, candidate))
                {
                    LockUnsafeConfirm("CGM WIRING OR IDENTITY CHANGED - ORIGINAL LOGIN BLOCKED");
                    yield break;
                }
                // Only a fully implemented original source project can use this unchanged callback.
                // In AssetRipper exports Login() is a stub and cannot authenticate.
                originalLoginBusy = true;
                SetStatus("CGM ACTIVE until " + expiryUtc + " - CALLING UNCHANGED ORIGINAL LOGIN");
                try { unchangedOriginalLogin.Invoke(); }
                catch (Exception)
                {
                    originalLoginBusy = false;
                    SetStatus("ORIGINAL LOGIN ERROR - NO SESSION FABRICATED");
                }
#endif
            }
        }

        private bool SameAttemptAfterCheck(int epoch, string candidate)
        {
            return generation == epoch && existingUsernameField != null &&
                existingUsernameField.text == candidate && originalCredentialPanel != null &&
                originalCredentialPanel.activeInHierarchy && isActiveAndEnabled;
        }

        private bool SameAttempt(int epoch, string requestId, string candidate)
        {
            return checking && generation == epoch && pendingRequestId == requestId &&
                pendingEmail == candidate && existingUsernameField != null &&
                existingUsernameField.text == candidate && originalCredentialPanel != null &&
                originalCredentialPanel.activeInHierarchy && isActiveAndEnabled;
        }

        private void Update()
        {
            // A reconstructed manager may overwrite Button.onClick in Start or later.
            // The source installer MUST NOT claim effective gating after such a change.
            if (wired && !IsExclusiveWiringNow())
            {
                LockUnsafeConfirm("CGM CONFIRM CALLBACK OVERRIDDEN - SUBMISSION DISABLED");
                return;
            }
            // Original panel may close through a non-CANCEL path. A stale reply must not continue.
            if (checking && (originalCredentialPanel == null || !originalCredentialPanel.activeInHierarchy))
                CancelPending();
        }

        private void LateUpdate()
        {
            // Do not let a later script re-enable a known-unsafe normal UI button.
            // This cannot prevent unknown programmatic Login() calls elsewhere.
            if (!wired && innerConfirmButton != null && innerConfirmButton.interactable)
                innerConfirmButton.interactable = false;
        }

        private void Fail(int epoch, string message)
        {
            if (generation != epoch) return;
            checking = false;
            activeRoutine = null;
            inFlight = null;
            pendingEmail = null;
            pendingRequestId = null;
            SetStatus(message);
        }

        private void EmailEdited(string _)
        {
            // Even edit-away-and-back invalidates an earlier response.
            if (checking) CancelPending();
        }

        public void CancelPending()
        {
            ++generation;
            checking = false;
            pendingEmail = null;
            pendingRequestId = null;
            if (inFlight != null) { inFlight.Abort(); inFlight = null; }
            if (activeRoutine != null) { StopCoroutine(activeRoutine); activeRoutine = null; }
            SetStatus("CGM CHECK CANCELED - ORIGINAL LOGIN BLOCKED");
        }

        // Must be called by genuine original login completion/rejection/logout in source integrations.
        // Until connected, further logins fail closed rather than double submitting.
        public void NotifyOriginalLoginFinished()
        {
            originalLoginBusy = false;
            CancelPending();
        }

        private void OnDisable()
        {
            CancelPending();
            if (subscribed && existingUsernameField != null)
            {
                existingUsernameField.onValueChanged.RemoveListener(EmailEdited);
                subscribed = false;
            }
        }
        private void OnDestroy()
        {
            // OnDisable normally handles this; retaining idempotent teardown is safe.
            if (inFlight != null) inFlight.Abort();
        }
    }
}
