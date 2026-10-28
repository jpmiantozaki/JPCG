// Phase 2.8 replacement for the existing Phase 2.7 file at this exact path.
// Editor/desktop Unity ONLY. CGM membership precheck; no game credentials or sessions.
using System;
using System.Collections;
using System.Text;
using CGM.AuthPrototype;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

namespace CGM.OriginalUI
{
    public static class CGMOriginalMembershipBootstrap
    {
        [RuntimeInitializeOnLoadMethod(RuntimeInitializeLoadType.AfterSceneLoad)]
        private static void Install()
        {
            Scene scene = SceneManager.GetActiveScene();
            if (!scene.IsValid() || !scene.name.StartsWith("GardenUI", StringComparison.Ordinal)) return;
            LoginServerManager[] managers = UnityEngine.Object.FindObjectsOfType<LoginServerManager>(true);
            if (managers.Length != 1)
            {
                Debug.LogWarning("CGM 2.8: expected one original LoginServerManager; adapter not installed.");
                return;
            }
            if (managers[0].GetComponent<CGMOriginalMembershipGate>() == null)
                managers[0].gameObject.AddComponent<CGMOriginalMembershipGate>();
        }
    }

    [DefaultExecutionOrder(900)]
    public sealed class CGMOriginalMembershipGate : MonoBehaviour
    {
        private const string Schema = "cgm-email-membership-v1";
        [SerializeField] private int gatewayPort = 18996;
        private GameObject originalPanel;
        private Button confirmButton;
        private Button cancelButton;
        private Button.ButtonClickedEvent savedConfirmEvent;
        private Button.ButtonClickedEvent savedCancelEvent;
        private Button.ButtonClickedEvent phaseConfirmEvent;
        private Button.ButtonClickedEvent phaseCancelEvent;
        private Coroutine currentRequest;
        private UnityWebRequest inFlight;
        private int generation;
        private bool pending;
        private bool previousVisible;
        private float startedAt;
        private string email = "";
        private string state = "NOT CHECKED";
        private string detail = "CGM eligibility only. Official PlayPark authentication: NOT VERIFIED.";

        [Serializable]
        private sealed class EmailRequest
        {
            public string schema;
            public string email;
            public string request_id;
        }
        [Serializable]
        private sealed class GatewayResponse
        {
            public string schema;
            public string request_id;
            public string status;
            public string gateway_mode;
            public long server_time;
            public long membership_expires_at;
            public long ttl_ms;
            public bool official_game_auth;
        }

        private IEnumerator Start()
        {
            // The reconstructed LoginServerManager.Start() first assigns the
            // original three offline button events. Do not read broken AssetRipper
            // persistent-event internals, and never add a second listener.
            yield return null;
            GameObject root = null;
            foreach (GameObject go in SceneManager.GetActiveScene().GetRootGameObjects())
                if (go.name == "Canvas") { root = go; break; }
            Transform login = root != null ? root.transform.Find("Login") : null;
            Transform panel = login != null ? login.Find("imgLoginBase") : null;
            Transform confirm = panel != null ? panel.Find("btnLogin") : null;
            Transform cancel = panel != null ? panel.Find("btnCancel") : null;
            originalPanel = panel != null ? panel.gameObject : null;
            confirmButton = confirm != null ? confirm.GetComponent<Button>() : null;
            cancelButton = cancel != null ? cancel.GetComponent<Button>() : null;
            if (originalPanel == null || confirmButton == null || cancelButton == null)
            {
                Debug.LogError("CGM 2.8: original Login/CONFIRM/CANCEL missing; no callbacks modified.");
                enabled = false;
                yield break;
            }
            savedConfirmEvent = confirmButton.onClick;
            savedCancelEvent = cancelButton.onClick;
            phaseConfirmEvent = new Button.ButtonClickedEvent();
            phaseConfirmEvent.AddListener(BeginCheck);
            phaseCancelEvent = new Button.ButtonClickedEvent();
            phaseCancelEvent.AddListener(CancelAndDelegate);
            confirmButton.onClick = phaseConfirmEvent;
            cancelButton.onClick = phaseCancelEvent;
            Debug.Log("CGM 2.8 READY: original buttons, localhost 18996 membership-only; game auth disabled.");
        }

        private void Update()
        {
            if (originalPanel == null) return;
            bool visible = originalPanel.activeInHierarchy;
            if (visible && !previousVisible)
            {
                // Recovered original game login methods are stubs; this experiment
                // never accepts or retains a real game username/password.
                foreach (InputField input in originalPanel.GetComponentsInChildren<InputField>(true))
                {
                    input.text = "";
                    input.interactable = false;
                }
                state = "NOT CHECKED";
                detail = "Enter only your CGM membership email below (not a game password).";
            }
            if (!visible && previousVisible) ResetGate();
            previousVisible = visible;
            if (pending && Time.realtimeSinceStartup - startedAt > 35f)
            {
                Invalidate();
                state = "CGM CHECK ERROR";
                detail = "Gateway timed out. Fail-closed; retry is available.";
            }
            if (confirmButton != null) confirmButton.interactable = visible && !pending;
        }

        private void CancelAndDelegate()
        {
            ResetGate();
            // Preserve the pre-existing reconstructed CANCEL behavior exactly once.
            if (savedCancelEvent != null) savedCancelEvent.Invoke();
        }

        private void BeginCheck()
        {
            if (pending || originalPanel == null || !originalPanel.activeInHierarchy) return;
            string candidate = email.Trim();
            if (!CGMRealMembership.IsEmailCandidate(candidate))
            {
                state = "INVALID CGM EMAIL";
                detail = "Enter your own CGM membership email. No game login performed.";
                return;
            }
            if (gatewayPort < 1024 || gatewayPort > 65535)
            {
                state = "CGM CONFIG ERROR";
                detail = "Local gateway port is invalid. Fail-closed.";
                return;
            }
            // Do not leave email in an on-screen input or any debug log.
            email = "";
            string id = Guid.NewGuid().ToString("N");
            int thisGeneration = ++generation;
            pending = true;
            startedAt = Time.realtimeSinceStartup;
            state = "CHECKING CGM...";
            detail = "Membership request to localhost only. No official login attempted.";
            currentRequest = StartCoroutine(CheckGateway(candidate, id, thisGeneration));
        }

        private IEnumerator CheckGateway(string candidate, string id, int thisGeneration)
        {
            var payload = new EmailRequest { schema = Schema, email = candidate, request_id = id };
            byte[] bytes = Encoding.UTF8.GetBytes(JsonUtility.ToJson(payload));
            string url = "http://127.0.0.1:" + gatewayPort + "/membership";
            using (var request = new UnityWebRequest(url, "POST"))
            {
                request.uploadHandler = new UploadHandlerRaw(bytes);
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Content-Type", "application/json");
                request.timeout = 28;
                inFlight = request;
                yield return request.SendWebRequest();
                if (ReferenceEquals(inFlight, request)) inFlight = null;
                if (!pending || generation != thisGeneration || originalPanel == null ||
                    !originalPanel.activeInHierarchy) yield break;
                pending = false;
                currentRequest = null;
                if (request.result != UnityWebRequest.Result.Success || request.responseCode != 200)
                {
                    state = "CGM GATEWAY ERROR";
                    detail = "Membership gateway unavailable or returned an HTTP error. Fail-closed.";
                    yield break;
                }
                GatewayResponse result = null;
                try { result = JsonUtility.FromJson<GatewayResponse>(request.downloadHandler.text); }
                catch (Exception) { /* Untrusted malformed JSON. */ }
                if (result == null || result.schema != Schema || result.request_id != id ||
                    result.official_game_auth || (result.status != "ACTIVE" &&
                    result.status != "INACTIVE" && result.status != "ERROR"))
                {
                    state = "INVALID CGM REPLY";
                    detail = "Schema, request identity, or status mismatch. Fail-closed.";
                    yield break;
                }
                if (result.gateway_mode == "OFFLINE_FIXTURE_NOT_BACKEND")
                {
                    state = "CGM OFFLINE FIXTURE";
                    detail = "Offline fixture is not membership evidence. Official game auth: NO.";
                    yield break;
                }
                if (result.gateway_mode != "LIVE_CGM_BACKEND")
                {
                    state = "INVALID GATEWAY MODE";
                    detail = "Unrecognized response provenance. Fail-closed.";
                    yield break;
                }
                if (result.status == "INACTIVE")
                {
                    state = "CGM INACTIVE (LIVE BACKEND)";
                    detail = "No active CGM membership. Game login remains blocked.";
                    yield break;
                }
                if (result.status == "ERROR")
                {
                    state = "CGM BACKEND ERROR";
                    detail = "JPCG membership check failed. Game login remains blocked.";
                    yield break;
                }
                long localTime = DateTimeOffset.UtcNow.ToUnixTimeSeconds();
                if (result.server_time < localTime - 300 || result.server_time > localTime + 300 ||
                    result.membership_expires_at <= result.server_time ||
                    result.membership_expires_at <= localTime ||
                    result.membership_expires_at > 253402300799L ||
                    result.ttl_ms <= 0 || result.ttl_ms > 5000)
                {
                    state = "CGM EXPIRED / INVALID";
                    detail = "Membership expiry, TTL, or server time failed validation. Fail-closed.";
                    yield break;
                }
                state = "CGM ACTIVE (LIVE BACKEND)";
                string expiry = DateTimeOffset.FromUnixTimeSeconds(result.membership_expires_at)
                    .UtcDateTime.ToString("yyyy-MM-dd HH:mm 'UTC'");
                detail = "CGM membership expires " + expiry +
                    ". This is NOT proof of email ownership or PlayPark login. No game continuation.";
                Debug.Log("CGM 2.8: LIVE membership ACTIVE (eligibility only); official game auth NOT VERIFIED.");
            }
        }

        private void Invalidate()
        {
            ++generation;
            if (inFlight != null)
            {
                inFlight.Abort();
                inFlight = null;
            }
            if (currentRequest != null) StopCoroutine(currentRequest);
            currentRequest = null;
            pending = false;
        }
        private void ResetGate()
        {
            Invalidate();
            email = "";
            state = "NOT CHECKED";
            detail = "CGM eligibility only. Official game auth NOT VERIFIED.";
        }
        private void OnDisable() { ResetGate(); }
        private void OnDestroy()
        {
            Invalidate();
            // Restore in-memory callbacks if our own replacements are still there.
            if (confirmButton != null && ReferenceEquals(confirmButton.onClick, phaseConfirmEvent))
                confirmButton.onClick = savedConfirmEvent;
            if (cancelButton != null && ReferenceEquals(cancelButton.onClick, phaseCancelEvent))
                cancelButton.onClick = savedCancelEvent;
        }
        private void OnGUI()
        {
            if (originalPanel == null || !originalPanel.activeInHierarchy) return;
            GUI.depth = -300;
            float width = Mathf.Min(425f, Screen.width - 12f);
            float x = Mathf.Max(6f, Screen.width - width - 8f);
            float y = Mathf.Max(6f, Screen.height - 206f);
            GUI.Box(new Rect(x, y, width, 200f), "CGM 2.8 - ORIGINAL CONFIRM / MEMBERSHIP ONLY");
            GUI.Label(new Rect(x + 12, y + 28, width - 24, 36),
                "Local gateway: 127.0.0.1:" + gatewayPort + " | PlayPark auth: NOT VERIFIED");
            GUI.enabled = !pending;
            email = GUI.TextField(new Rect(x + 12, y + 66, width - 24, 24), email, 254);
            if (GUI.Button(new Rect(x + 12, y + 94, width - 24, 26),
                pending ? "Checking..." : "CHECK CGM EMAIL (or original CONFIRM)")) BeginCheck();
            GUI.enabled = true;
            GUI.Label(new Rect(x + 12, y + 124, width - 24, 23), state);
            GUI.Label(new Rect(x + 12, y + 149, width - 24, 48), detail);
        }
    }
}
