// Editor-only source-level installer. This is NOT an IL2CPP/APK injector.
// Run on an explicitly backed-up, AUTHORIZED Unity source scene and inspect the diff before saving.
#if UNITY_EDITOR
using System;
using CGM.AuthFirst;
using UnityEditor;
using UnityEditor.Events;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.Events;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public static class CgmAuthFirstSceneBinder
{
    private static Transform SceneCanvas()
    {
        foreach (GameObject root in SceneManager.GetActiveScene().GetRootGameObjects())
            if (root.name == "Canvas") return root.transform;
        return null;
    }

    private static bool GetOriginalObjects(out Button confirm, out Button cancel,
        out InputField username, out LoginServerManager loginManager)
    {
        confirm = null; cancel = null; username = null; loginManager = null;
        Transform canvas = SceneCanvas();
        Transform login = canvas != null ? canvas.Find("Login/imgLoginBase") : null;
        if (login == null) return false;
        Transform confirmPath = login.Find("btnLogin");
        Transform cancelPath = login.Find("btnCancel");
        Transform usernamePath = login.Find("txtUsername");
        confirm = confirmPath != null ? confirmPath.GetComponent<Button>() : null;
        cancel = cancelPath != null ? cancelPath.GetComponent<Button>() : null;
        username = usernamePath != null ? usernamePath.GetComponent<InputField>() : null;
        LoginServerManager[] managers = UnityEngine.Object.FindObjectsOfType<LoginServerManager>(true);
        if (managers.Length != 1) return false;
        loginManager = managers[0];
        return confirm != null && cancel != null && username != null && loginManager != null;
    }

    // Exact original callback and component identity from archived Phase 2.6H.
    private static bool OriginalCallbackMatches(Button button, LoginServerManager manager)
    {
        try
        {
            return button.onClick.GetPersistentEventCount() == 1 &&
                button.onClick.GetPersistentMethodName(0) == "Login" &&
                button.onClick.GetPersistentTarget(0) == manager;
        }
        catch (Exception) { return false; }
    }

    private static bool GateCallbackMatches(Button button, CgmAuthFirstSubmitGate gate)
    {
        try
        {
            return button.onClick.GetPersistentEventCount() == 1 &&
                button.onClick.GetPersistentMethodName(0) == "Submit" &&
                button.onClick.GetPersistentTarget(0) == gate;
        }
        catch (Exception) { return false; }
    }

    [MenuItem("CGM/Auth-First Source Gate/Install into original CONFIRM (Edit Mode)")]
    public static void Install()
    {
        if (EditorApplication.isPlayingOrWillChangePlaymode)
        {
            Debug.LogError("CGM INSTALL BLOCKED: stop Play Mode first."); return;
        }
        Button confirm, cancel;
        InputField username;
        LoginServerManager manager;
        if (SceneManager.GetActiveScene().name != "GardenUI" ||
            !GetOriginalObjects(out confirm, out cancel, out username, out manager))
        {
            Debug.LogError("CGM INSTALL BLOCKED: expected original GardenUI Canvas/Login/imgLoginBase objects and one LoginServerManager.");
            return;
        }
        if (!OriginalCallbackMatches(confirm, manager))
        {
            Debug.LogError("CGM INSTALL BLOCKED: inner CONFIRM must have exactly one persistent original Login() listener. No scene change made.");
            return;
        }
        try
        {
            if (cancel.onClick.GetPersistentEventCount() != 1)
            {
                Debug.LogError("CGM INSTALL BLOCKED: expected exactly one existing CANCEL callback; no changes made.");
                return;
            }
        }
        catch (Exception)
        {
            Debug.LogError("CGM INSTALL BLOCKED: cannot safely inspect original CANCEL event.");
            return;
        }
        if (manager.GetComponent<CgmAuthFirstSubmitGate>() != null)
        {
            Debug.LogError("CGM INSTALL BLOCKED: gate already exists; do not install a second.");
            return;
        }
        // Do not install alongside the old runtime membership adapter: two owners could race.
        if (manager.GetComponent("CGMOriginalMembershipGate") != null)
        {
            Debug.LogError("CGM INSTALL BLOCKED: previous Phase 2.8 gate is present. Remove it from the authorized source-scene copy first.");
            return;
        }
        // The previous Phase 2.8 gate auto-installs at runtime, even when absent in Edit Mode.
        // Detect the compiled type as well as an attached component; never race two adapters.
        foreach (System.Reflection.Assembly assembly in AppDomain.CurrentDomain.GetAssemblies())
        {
            if (assembly.GetType("CGM.OriginalUI.CGMOriginalMembershipBootstrap", false) != null)
            {
                Debug.LogError("CGM INSTALL BLOCKED: older CGMOriginalMembershipBootstrap script is still compiled. Remove it from a separate source-project copy first.");
                return;
            }
        }
        // Confirm original intent before changing any serialized callback.
        if (!EditorUtility.DisplayDialog("CGM source gate install",
            "Replace ONLY the inner CONFIRM direct Login() callback with CGM precheck? " +
            "The original Login() will be preserved as a separate continuation, DISABLED for testing. " +
            "Back up the scene first. This does not install into the original APK.",
            "Install source gate", "Cancel")) return;

        Undo.RecordObject(confirm, "CGM Auth-First inner CONFIRM callback");
        Undo.RecordObject(cancel, "CGM Auth-First cancel callback");
        var gate = Undo.AddComponent<CgmAuthFirstSubmitGate>(manager.gameObject);
        Undo.RecordObject(gate, "CGM Auth-First gate references");
        gate.EditorConfigure(confirm, username);
        UnityEventTools.AddPersistentListener(gate.OriginalLoginContinuation, new UnityAction(manager.Login));
        gate.OriginalLoginContinuation.SetPersistentListenerState(0, UnityEventCallState.RuntimeOnly);

        // DO NOT use AddListener alongside the original onClick: that would bypass the gate.
        UnityEventTools.RemovePersistentListener(confirm.onClick, 0);
        UnityEventTools.AddPersistentListener(confirm.onClick, new UnityAction(gate.Submit));
        confirm.onClick.SetPersistentListenerState(0, UnityEventCallState.RuntimeOnly);

        // Preserve CANCEL's existing action and append only our in-flight invalidation.
        bool hadCancelListener = false;
        try
        {
            for (int i = 0; i < cancel.onClick.GetPersistentEventCount(); i++)
                if (cancel.onClick.GetPersistentTarget(i) == gate &&
                    cancel.onClick.GetPersistentMethodName(i) == "CancelPending") hadCancelListener = true;
        }
        catch (Exception) { /* Do not add cancellation hook if introspection fails. */ hadCancelListener = true; }
        if (!hadCancelListener)
            UnityEventTools.AddPersistentListener(cancel.onClick, new UnityAction(gate.CancelPending));

        EditorUtility.SetDirty(confirm);
        EditorUtility.SetDirty(cancel);
        EditorUtility.SetDirty(gate);
        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        Debug.Log("CGM SOURCE GATE INSTALLED (unsaved): inner CONFIRM now gates; original Login() retained once; " +
            "continuation is TEST-ONLY/DISABLED. Inspect scene diff and save explicitly.");
    }

    [MenuItem("CGM/Auth-First Source Gate/Validate inner CONFIRM wiring (Read Only)")]
    public static void Validate()
    {
        Button confirm, cancel;
        InputField username;
        LoginServerManager manager;
        if (!GetOriginalObjects(out confirm, out cancel, out username, out manager))
        {
            Debug.LogError("CGM VALIDATION FAILED: original UI components missing."); return;
        }
        var gate = manager.GetComponent<CgmAuthFirstSubmitGate>();
        if (gate == null || !GateCallbackMatches(confirm, gate) ||
            gate.OriginalLoginContinuation.GetPersistentEventCount() != 1 ||
            gate.OriginalLoginContinuation.GetPersistentMethodName(0) != "Login" ||
            gate.OriginalLoginContinuation.GetPersistentTarget(0) != manager)
        {
            Debug.LogError("CGM VALIDATION FAILED: direct Login listener still present or continuation not preserved exactly once.");
            return;
        }
        Debug.Log("CGM VALIDATION PASS: one gated inner CONFIRM; original Login() stored exactly once; " +
            "outer yellow LOGIN callback unchanged by installer.");
    }

    [MenuItem("CGM/Auth-First Source Gate/Revert original CONFIRM (Edit Mode)")]
    public static void Revert()
    {
        if (EditorApplication.isPlayingOrWillChangePlaymode)
        {
            Debug.LogError("CGM REVERT BLOCKED: stop Play Mode first."); return;
        }
        Button confirm, cancel;
        InputField username;
        LoginServerManager manager;
        if (!GetOriginalObjects(out confirm, out cancel, out username, out manager))
        {
            Debug.LogError("CGM REVERT BLOCKED: objects missing."); return;
        }
        var gate = manager.GetComponent<CgmAuthFirstSubmitGate>();
        if (gate == null || !GateCallbackMatches(confirm, gate))
        {
            Debug.LogError("CGM REVERT BLOCKED: expected installed gate wiring not present. No changes made."); return;
        }
        Undo.RecordObject(confirm, "Restore original CONFIRM callback");
        Undo.RecordObject(cancel, "Remove gate cancellation callback");
        UnityEventTools.RemovePersistentListener(confirm.onClick, 0);
        UnityEventTools.AddPersistentListener(confirm.onClick, new UnityAction(manager.Login));
        confirm.onClick.SetPersistentListenerState(0, UnityEventCallState.RuntimeOnly);
        try
        {
            for (int i = cancel.onClick.GetPersistentEventCount() - 1; i >= 0; i--)
                if (cancel.onClick.GetPersistentTarget(i) == gate &&
                    cancel.onClick.GetPersistentMethodName(i) == "CancelPending")
                    UnityEventTools.RemovePersistentListener(cancel.onClick, i);
        }
        catch (Exception) { Debug.LogWarning("CGM: review CANCEL listeners manually after revert."); }
        Undo.DestroyObjectImmediate(gate);
        EditorUtility.SetDirty(confirm);
        EditorUtility.SetDirty(cancel);
        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        Debug.Log("CGM SOURCE GATE REVERTED (unsaved): direct original Login() CONFIRM binding restored.");
    }
}
#endif
