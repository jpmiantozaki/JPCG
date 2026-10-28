// Phase 2.8: original GardenUI rendering proof without serialized scene edits.
// All five PNGs are the ORIGINAL recovered assets, not recreated artwork.
#if UNITY_EDITOR
using System;
using System.Collections.Generic;
using UnityEditor;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public static class CGMPhase28OriginalUIRenderProbe
{
    private struct Target
    {
        public readonly string path;
        public readonly string spriteName;
        public Target(string path, string spriteName) { this.path = path; this.spriteName = spriteName; }
    }
    private struct Previous
    {
        public Sprite sprite;
        public Sprite overrideSprite;
        public bool enabled;
    }
    private static readonly Target[] targets =
    {
        new Target("Login", "intro - anl29"),
        new Target("Login/btnLogin", "login_btn_1-min"),
        new Target("Login/imgLoginBase", "logbase"),
        new Target("Login/imgLoginBase/btnLogin", "confirm_1"),
        new Target("Login/imgLoginBase/btnCancel", "cancel_1")
    };
    private static readonly Dictionary<Image, Previous> originals = new Dictionary<Image, Previous>();

    private static Transform FindCanvas()
    {
        Scene scene = SceneManager.GetActiveScene();
        if (!EditorApplication.isPlaying || !scene.IsValid() ||
            !scene.name.StartsWith("GardenUI", StringComparison.Ordinal))
        {
            Debug.LogError("CGM 2.8 RENDER: open GardenUI and enter Play Mode first. No edits made.");
            return null;
        }
        foreach (GameObject root in scene.GetRootGameObjects())
            if (root.name == "Canvas") return root.transform;
        Debug.LogError("CGM 2.8 RENDER: root Canvas not found.");
        return null;
    }

    [MenuItem("CGM/Original UI/2.8 - Diagnose Original Rendering (Play Mode)")]
    public static void Diagnose()
    {
        Transform root = FindCanvas();
        if (root == null) return;
        Canvas canvas = root.GetComponent<Canvas>();
        Debug.Log("CGM 2.8 RENDER START scene=" + SceneManager.GetActiveScene().name +
                  " size=" + Screen.width + "x" + Screen.height +
                  " canvasActive=" + root.gameObject.activeInHierarchy +
                  " canvasEnabled=" + (canvas != null && canvas.enabled) +
                  " renderMode=" + (canvas != null ? canvas.renderMode.ToString() : "missing") +
                  " canvasScale=" + root.localScale +
                  " camera=" + (Camera.main != null ? "enabled=" + Camera.main.enabled : "missing"));
        foreach (Target target in targets)
        {
            Transform t = root.Find(target.path);
            if (t == null) { Debug.LogError("CGM 2.8 RENDER missing " + target.path); continue; }
            Image img = t.GetComponent<Image>();
            if (img == null) { Debug.LogError("CGM 2.8 RENDER missing Image at " + target.path); continue; }
            Sprite sprite = img.sprite;
            string asset = sprite != null ? AssetDatabase.GetAssetPath(sprite) : "NULL";
            string texture = "NULL";
            if (sprite != null)
            {
                try { texture = sprite.texture != null ? sprite.texture.name : "NULL"; }
                catch (Exception) { texture = "BROKEN"; }
            }
            float groupAlpha = 1f;
            foreach (CanvasGroup g in t.GetComponentsInParent<CanvasGroup>(true)) groupAlpha *= g.alpha;
            RectTransform rect = t as RectTransform;
            string bounds = "none";
            if (rect != null)
            {
                Vector3[] corners = new Vector3[4]; rect.GetWorldCorners(corners);
                Vector2 a = RectTransformUtility.WorldToScreenPoint(null, corners[0]);
                Vector2 b = RectTransformUtility.WorldToScreenPoint(null, corners[2]);
                bounds = a + " to " + b;
            }
            Debug.Log("CGM 2.8 RENDER " + target.path +
                      " active=" + t.gameObject.activeInHierarchy +
                      " enabled=" + img.enabled +
                      " sprite=" + (sprite != null ? sprite.name : "NULL") +
                      " spriteAsset=" + asset +
                      " texture=" + texture +
                      " alpha=" + img.color.a +
                      " groupAlpha=" + groupAlpha +
                      " rendererCull=" + img.canvasRenderer.cull +
                      " rendererAlpha=" + img.canvasRenderer.GetAlpha() +
                      " scale=" + t.lossyScale + " bounds=" + bounds);
        }
        Transform login = root.Find("Login");
        int visibleSiblings = 0;
        foreach (Transform sibling in root)
        {
            if (sibling == login || !sibling.gameObject.activeInHierarchy) continue;
            visibleSiblings++;
            if (visibleSiblings <= 8)
                Debug.Log("CGM 2.8 RENDER visible Canvas sibling: " + sibling.name +
                          " siblingIndex=" + sibling.GetSiblingIndex());
        }
        Debug.Log("CGM 2.8 RENDER END nonLoginActiveCanvasSiblings=" + visibleSiblings +
                  " previewOriginalPNGs=" + (originals.Count > 0) +
                  " gameAuthentication=NOT_VERIFIED");
    }

    [MenuItem("CGM/Original UI/2.8 - Preview Original PNG Sprites (Play Mode)")]
    public static void Preview()
    {
        Transform root = FindCanvas();
        if (root == null) return;
        if (originals.Count != 0)
        {
            Debug.LogWarning("CGM 2.8 RENDER: PNG preview already active. Revert it first.");
            return;
        }
        // Validate every target and original importer before changing anything.
        List<Image> images = new List<Image>();
        List<Sprite> sprites = new List<Sprite>();
        foreach (Target target in targets)
        {
            Transform t = root.Find(target.path);
            Image img = t != null ? t.GetComponent<Image>() : null;
            string png = "Assets/Texture2D/" + target.spriteName + ".png";
            Sprite sprite = AssetDatabase.LoadAssetAtPath<Sprite>(png);
            if (img == null || sprite == null || sprite.texture == null)
            {
                Debug.LogError("CGM 2.8 RENDER: preflight failed for " + target.path +
                               " and " + png + ". NO changes made. Check Sprite importer in Inspector.");
                return;
            }
            images.Add(img); sprites.Add(sprite);
        }
        for (int i = 0; i < images.Count; ++i)
        {
            Image img = images[i];
            originals.Add(img, new Previous { sprite = img.sprite,
                overrideSprite = img.overrideSprite, enabled = img.enabled });
            img.sprite = sprites[i];
            img.overrideSprite = null;
            img.enabled = true;
        }
        Canvas.ForceUpdateCanvases();
        Debug.Log("CGM 2.8 RENDER PREVIEW APPLIED: 5 ORIGINAL PNG sprites assigned in Play Mode only. " +
                  "No importer settings, .meta files, serialized scene, or credentials modified. " +
                  "Inspect Game view, take screenshot, then use Revert Preview or stop Play Mode.");
        Diagnose();
    }

    [MenuItem("CGM/Original UI/2.8 - Revert PNG Preview (Play Mode)")]
    public static void Revert()
    {
        if (!EditorApplication.isPlaying) { originals.Clear(); return; }
        int count = 0;
        foreach (var item in originals)
        {
            if (item.Key == null) continue;
            item.Key.sprite = item.Value.sprite;
            item.Key.overrideSprite = item.Value.overrideSprite;
            item.Key.enabled = item.Value.enabled;
            count++;
        }
        originals.Clear();
        Canvas.ForceUpdateCanvases();
        Debug.Log("CGM 2.8 RENDER PREVIEW REVERTED: " + count + " Image references restored.");
    }
}
#endif
