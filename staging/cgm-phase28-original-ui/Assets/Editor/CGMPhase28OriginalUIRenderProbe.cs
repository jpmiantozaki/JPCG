// CGM Auth-First Phase 2.8.2: reversible original GardenUI rendering recovery.
// This Editor-only file replaces the previous Phase 2.8 rendering probe at EXACTLY the same path.
// It never saves the scene, rewrites asset import settings, handles credentials, or changes game auth.
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
        public Target(string path, string spriteName)
        {
            this.path = path;
            this.spriteName = spriteName;
        }
    }

    private struct Previous
    {
        public Sprite sprite;
        public Sprite overrideSprite;
        public Color color;
        public bool enabled;
        public CanvasRenderer addedRenderer;
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
    private static readonly List<Sprite> createdPreviewSprites = new List<Sprite>();
    private static GameObject pipelineCanary;
    private static readonly Dictionary<MonoBehaviour, bool> hiddenGatewayOverlay =
        new Dictionary<MonoBehaviour, bool>();

    static CGMPhase28OriginalUIRenderProbe()
    {
        EditorApplication.playModeStateChanged += OnPlayModeChange;
    }

    private static void OnPlayModeChange(PlayModeStateChange state)
    {
        if (state == PlayModeStateChange.ExitingPlayMode)
        {
            // Restore even with Unity Enter Play Mode scene-reload optimizations.
            RevertInternal(false);
        }
    }

    private static void DestroyPreviewSprites()
    {
        foreach (Sprite sprite in createdPreviewSprites)
        {
            if (sprite != null)
                UnityEngine.Object.DestroyImmediate(sprite);
        }
        createdPreviewSprites.Clear();
    }

    private static string Try(Func<string> read)
    {
        try { return read(); }
        catch (Exception ex) { return "ERROR(" + ex.GetType().Name + ")"; }
    }

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
        {
            if (root != null && root.name == "Canvas") return root.transform;
        }
        Debug.LogError("CGM 2.8 RENDER: root Canvas not found. No edits made.");
        return null;
    }

    private static string DescribeCanvasRenderer(Image image)
    {
        return Try(() =>
        {
            // Distinguish the actual component from Graphic's cached property.
            CanvasRenderer component = image.GetComponent<CanvasRenderer>();
            CanvasRenderer graphicRenderer = image.canvasRenderer;
            if (graphicRenderer == null)
                return "graphicRenderer=NULL component=" + (component == null ? "NULL" : "PRESENT");
            return "graphicRenderer=PRESENT component=" + (component == null ? "NULL" : "PRESENT") +
                   " cull=" + Try(() => graphicRenderer.cull.ToString()) +
                   " rendererAlpha=" + Try(() => graphicRenderer.GetAlpha().ToString("F3"));
        });
    }

    private static string DescribeSprite(Image image)
    {
        return Try(() =>
        {
            Sprite sprite = image.sprite;
            if (sprite == null) return "sprite=NULL asset=NULL texture=NULL";
            return "sprite=" + Try(() => sprite.name) +
                   " asset=" + Try(() => AssetDatabase.GetAssetPath(sprite)) +
                   " texture=" + Try(() => sprite.texture != null ? sprite.texture.name : "NULL");
        });
    }

    private static string DescribeGroupAlpha(Transform t)
    {
        return Try(() =>
        {
            float alpha = 1f;
            foreach (CanvasGroup group in t.GetComponentsInParent<CanvasGroup>(true))
                if (group != null) alpha *= group.alpha;
            return alpha.ToString("F3");
        });
    }

    private static string DescribeBounds(Transform t)
    {
        return Try(() =>
        {
            RectTransform rect = t as RectTransform;
            if (rect == null) return "NOT_RECT_TRANSFORM";
            Vector3[] corners = new Vector3[4];
            rect.GetWorldCorners(corners);
            Vector2 a = RectTransformUtility.WorldToScreenPoint(null, corners[0]);
            Vector2 b = RectTransformUtility.WorldToScreenPoint(null, corners[2]);
            return a + " to " + b;
        });
    }

    [MenuItem("CGM/Original UI/2.8 - Diagnose Original Rendering (Play Mode)")]
    public static void Diagnose()
    {
        Transform root = FindCanvas();
        if (root == null) return;
        Canvas canvas = root.GetComponent<Canvas>();
        string mainCamera = Try(() =>
        {
            Camera camera = Camera.main;
            return camera != null ? "enabled=" + camera.enabled : "missing";
        });
        Debug.Log("CGM 2.8 RENDER START version=2.8.2 scene=" + SceneManager.GetActiveScene().name +
                  " screen=" + Screen.width + "x" + Screen.height +
                  " canvasActive=" + Try(() => root.gameObject.activeInHierarchy.ToString()) +
                  " canvasEnabled=" + Try(() => (canvas != null && canvas.enabled).ToString()) +
                  " renderMode=" + Try(() => canvas != null ? canvas.renderMode.ToString() : "missing") +
                  " canvasScale=" + Try(() => root.localScale.ToString()) +
                  " camera=" + mainCamera);

        foreach (Target target in targets)
        {
            Transform t = null;
            try { t = root.Find(target.path); }
            catch (Exception ex)
            {
                Debug.LogWarning("CGM 2.8 RENDER path lookup error " + target.path +
                                 " exception=" + ex.GetType().Name);
                continue;
            }
            if (t == null)
            {
                Debug.LogWarning("CGM 2.8 RENDER missing " + target.path);
                continue;
            }
            Image img = t.GetComponent<Image>();
            if (img == null)
            {
                Debug.LogWarning("CGM 2.8 RENDER missing Image at " + target.path);
                continue;
            }

            // Independent protected reads avoid the Phase 2.8 NullReferenceException
            // that used to occur while constructing a single Debug.Log at line 88.
            Debug.Log("CGM 2.8 RENDER target=" + target.path +
                      " active=" + Try(() => t.gameObject.activeInHierarchy.ToString()) +
                      " imageEnabled=" + Try(() => img.enabled.ToString()) +
                      " colorRGBA=" + Try(() => img.color.ToString("F3")) +
                      " colorAlpha=" + Try(() => img.color.a.ToString("F3")) +
                      " groupAlpha=" + DescribeGroupAlpha(t));
            Debug.Log("CGM 2.8 RENDER target=" + target.path + " " + DescribeSprite(img));
            Debug.Log("CGM 2.8 RENDER target=" + target.path + " " + DescribeCanvasRenderer(img) +
                      " scale=" + Try(() => t.lossyScale.ToString()) +
                      " bounds=" + DescribeBounds(t));
        }

        Transform login = root.Find("Login");
        int activeSiblings = 0;
        foreach (Transform sibling in root)
        {
            if (sibling == null || sibling == login || !sibling.gameObject.activeInHierarchy)
                continue;
            activeSiblings++;
            if (activeSiblings <= 12)
            {
                Image image = sibling.GetComponent<Image>();
                Debug.Log("CGM 2.8 RENDER sibling=" + sibling.name +
                          " index=" + sibling.GetSiblingIndex() +
                          " hasImage=" + (image != null) +
                          " imageAlpha=" + (image != null ? Try(() => image.color.a.ToString("F3")) : "n/a"));
            }
        }
        Debug.Log("CGM 2.8 RENDER END activeNonLoginSiblings=" + activeSiblings +
                  " previewActive=" + (originals.Count > 0) +
                  " gameAuthentication=NOT_VERIFIED");
    }

    [MenuItem("CGM/Original UI/2.8.2 - Preview Visible Login (Play Mode)")]
    public static void Preview()
    {
        Transform root = FindCanvas();
        if (root == null) return;
        if (originals.Count > 0)
        {
            Debug.LogWarning("CGM 2.8 RENDER: preview already active; revert first.");
            return;
        }

        List<Image> images = new List<Image>();
        List<Sprite> sprites = new List<Sprite>();
        try
        {
            // Preflight everything before touching ANY original Image.
            foreach (Target target in targets)
            {
                Transform t = root.Find(target.path);
                Image img = t != null ? t.GetComponent<Image>() : null;
                string png = "Assets/Texture2D/" + target.spriteName + ".png";
                Sprite sprite = AssetDatabase.LoadAssetAtPath<Sprite>(png);
                if (sprite == null)
                {
                    Texture2D texture = AssetDatabase.LoadAssetAtPath<Texture2D>(png);
                    if (texture != null && texture.width > 0 && texture.height > 0)
                    {
                        sprite = Sprite.Create(texture,
                            new Rect(0f, 0f, texture.width, texture.height),
                            new Vector2(0.5f, 0.5f), 100f);
                        sprite.name = target.spriteName + "_CGM282_PREVIEW";
                        sprite.hideFlags = HideFlags.DontSave;
                        createdPreviewSprites.Add(sprite);
                    }
                }
                if (img == null || sprite == null || sprite.texture == null)
                    throw new InvalidOperationException("Missing original Image or PNG: " + target.path + " | " + png);

                images.Add(img);
                sprites.Add(sprite);
            }

            // Store ALL pre-preview state, including alpha and components.
            for (int i = 0; i < images.Count; i++)
            {
                Image img = images[i];
                originals.Add(img, new Previous
                {
                    sprite = img.sprite,
                    overrideSprite = img.overrideSprite,
                    color = img.color,
                    enabled = img.enabled,
                    addedRenderer = null
                });
            }

            int renderersAdded = 0;
            for (int i = 0; i < images.Count; i++)
            {
                Image img = images[i];
                if (img.GetComponent<CanvasRenderer>() == null)
                {
                    // Runtime-only: never add a second renderer if one exists.
                    CanvasRenderer added = img.gameObject.AddComponent<CanvasRenderer>();
                    Previous state = originals[img];
                    state.addedRenderer = added;
                    originals[img] = state;
                    renderersAdded++;
                }
                img.sprite = sprites[i];
                img.overrideSprite = null;
                img.color = Color.white; // Archived GardenUI serialized these targets as white/alpha=1.
                img.enabled = true;
                img.SetAllDirty();
                Debug.Log("CGM 2.8 RENDER APPLY path=" + targets[i].path +
                          " sprite=" + (img.sprite != null ? img.sprite.name : "NULL") +
                          " alpha=" + img.color.a.ToString("F3") +
                          " renderer=" + (img.GetComponent<CanvasRenderer>() != null ? "PRESENT" : "NULL") +
                          " active=" + img.gameObject.activeInHierarchy);
            }
            Canvas.ForceUpdateCanvases();
            Debug.Log("CGM 2.8 RENDER PREVIEW_APPLIED version=2.8.2 images=" + images.Count +
                      " temporaryRenderersAdded=" + renderersAdded +
                      " persistedEdits=NONE officialGameAuth=NOT_VERIFIED");
            Diagnose();
        }
        catch (Exception ex)
        {
            Debug.LogError("CGM 2.8 RENDER PREVIEW_BLOCKED version=2.8.2 " +
                           ex.GetType().Name + ": " + ex.Message +
                           "; restoring original Play Mode values.");
            RevertInternal(true);
        }
    }

    // The existing local CGM membership probe draws through IMGUI and overlaps
    // the original Yellow LOGIN's screen area. Hide only that test panel, not the
    // localhost gateway service or the original membership adapter.
    [MenuItem("CGM/Original UI/2.8.2 - Hide Custom Gateway Overlay (Play Mode)")]
    public static void HideCustomGatewayOverlay()
    {
        if (FindCanvas() == null) return;
        if (hiddenGatewayOverlay.Count > 0)
        {
            Debug.LogWarning("CGM 2.8 RENDER OVERLAY: already hidden; Revert to restore.");
            return;
        }
        int found = 0;
        foreach (MonoBehaviour behaviour in UnityEngine.Object.FindObjectsOfType<MonoBehaviour>(true))
        {
            if (behaviour == null || behaviour.GetType().FullName != "CGM.LocalGateway.CGMLocalPortProbe")
                continue;
            found++;
            hiddenGatewayOverlay.Add(behaviour, behaviour.enabled);
            behaviour.enabled = false;
        }
        Debug.Log("CGM 2.8 RENDER GATEWAY_OVERLAY_HIDDEN count=" + found +
                  " gatewayServiceUnchanged=True sceneSaved=False");
    }

    // Independent UGUI renderer test for the case where repaired original Images remain black.
    // The diagnostic rectangle is not original art and is never persisted.
    [MenuItem("CGM/Original UI/2.8.2 - Toggle UGUI Canary (Play Mode)")]
    public static void ToggleUguiCanary()
    {
        Transform canvasRoot = FindCanvas();
        if (canvasRoot == null) return;
        if (pipelineCanary != null)
        {
            UnityEngine.Object.DestroyImmediate(pipelineCanary);
            pipelineCanary = null;
            Debug.Log("CGM 2.8 RENDER CANARY_DISABLED persistedEdits=NONE");
            return;
        }
        // Pure UGUI RawImage, explicitly supplied a white texture. No sprite asset or game packets.
        GameObject probe = new GameObject("CGM282_UGUI_CANARY_PLAYMODE_ONLY",
            typeof(RectTransform), typeof(CanvasRenderer), typeof(RawImage));
        probe.hideFlags = HideFlags.DontSave;
        probe.transform.SetParent(canvasRoot, false);
        RectTransform rect = probe.GetComponent<RectTransform>();
        rect.anchorMin = Vector2.one;
        rect.anchorMax = Vector2.one;
        rect.pivot = Vector2.one;
        rect.anchoredPosition = new Vector2(-16f, -16f);
        rect.sizeDelta = new Vector2(120f, 65f);
        RawImage image = probe.GetComponent<RawImage>();
        image.texture = Texture2D.whiteTexture;
        image.color = Color.magenta;
        image.raycastTarget = false;
        probe.transform.SetAsLastSibling();
        pipelineCanary = probe;
        Canvas.ForceUpdateCanvases();
        Debug.Log("CGM 2.8 RENDER CANARY_ENABLED expected=magenta_top_right " +
                  " active=" + probe.activeInHierarchy +
                  " renderer=" + (probe.GetComponent<CanvasRenderer>() != null ? "PRESENT" : "NULL") +
                  " persistedEdits=NONE");
    }

    [MenuItem("CGM/Original UI/2.8.2 - Revert Visible Login Preview (Play Mode)")]
    public static void Revert()
    {
        RevertInternal(true);
    }

    private static void RevertInternal(bool log)
    {
        int count = 0;
        int renderersRemoved = 0;
        foreach (KeyValuePair<Image, Previous> item in originals)
        {
            Image img = item.Key;
            if (img == null) continue;
            try
            {
                img.sprite = item.Value.sprite;
                img.overrideSprite = item.Value.overrideSprite;
                img.color = item.Value.color;
                img.enabled = item.Value.enabled;
                if (item.Value.addedRenderer != null)
                {
                    UnityEngine.Object.DestroyImmediate(item.Value.addedRenderer);
                    renderersRemoved++;
                }
                count++;
            }
            catch (Exception ex)
            {
                Debug.LogWarning("CGM 2.8 RENDER REVERT_WARNING type=" + ex.GetType().Name);
            }
        }
        originals.Clear();
        foreach (KeyValuePair<MonoBehaviour, bool> item in hiddenGatewayOverlay)
        {
            if (item.Key != null) item.Key.enabled = item.Value;
        }
        hiddenGatewayOverlay.Clear();
        if (pipelineCanary != null)
        {
            UnityEngine.Object.DestroyImmediate(pipelineCanary);
            pipelineCanary = null;
        }
        DestroyPreviewSprites();
        if (EditorApplication.isPlaying) Canvas.ForceUpdateCanvases();
        if (log)
            Debug.Log("CGM 2.8 RENDER REVERTED images=" + count +
                      " temporaryRenderersRemoved=" + renderersRemoved +
                      " persistedEdits=NONE");
    }
}
#endif
