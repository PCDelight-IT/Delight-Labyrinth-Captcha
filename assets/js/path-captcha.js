// ============================================================
// PCD Path Captcha – Debug Version (3x kleiner unten, Originalgröße beim Drag)
// + Mobile/Tablet: Tippen statt Drag
// ============================================================

// ------------------------------------------------------------
// SVG Renderer mit dynamischer viewBox + DEBUG
// ------------------------------------------------------------
function pcdCreatePathSVG(points, width, height, debugLabel = "") {
    const SVGNS = "http://www.w3.org/2000/svg";

    const xs = points.map(p => p[0]);
    const ys = points.map(p => p[1]);

    const minX = Math.min(...xs);
    const maxX = Math.max(...xs);
    const minY = Math.min(...ys);
    const maxY = Math.max(...ys);

    const pad = 10;

    const viewW = (maxX - minX) + pad * 2;
    const viewH = (maxY - minY) + pad * 2;

    const viewBox = `${minX - pad} ${minY - pad} ${viewW} ${viewH}`;

    const svg = document.createElementNS(SVGNS, "svg");
    svg.setAttribute("width", width);
    svg.setAttribute("height", height);
    svg.setAttribute("viewBox", viewBox);

    const poly = document.createElementNS(SVGNS, "polyline");
    poly.setAttribute("points", points.map(p => p.join(",")).join(" "));
    poly.setAttribute("stroke", "#000");
    poly.setAttribute("stroke-width", "4");
    poly.setAttribute("fill", "none");
    poly.setAttribute("stroke-linecap", "round");
    poly.setAttribute("stroke-linejoin", "round");

    svg.appendChild(poly);

    console.log("[PCD] pcdCreatePathSVG", debugLabel, {
        width,
        height,
        minX,
        maxX,
        minY,
        maxY,
        viewBox
    });

    return svg;
}

// ------------------------------------------------------------
// Maze Coordinates → Pixelpfad
// ------------------------------------------------------------
function pcdConvertMazePathToPixelPath(mazePath, cellSize) {
    return mazePath.map(p => [
        p.x * cellSize + cellSize / 2,
        p.y * cellSize + cellSize / 2
    ]);
}

function pcdFakeMirror(points, mazeWpx) {
    return points.map(([x, y]) => [mazeWpx - x, y]);
}

function pcdFakeRotate90(points, mazeWpx) {
    return points.map(([x, y]) => [y, mazeWpx - x]);
}

// ------------------------------------------------------------
// Captcha Initialisierung
// ------------------------------------------------------------
function pcdInitCaptcha(wrapper) {

    const canvas = wrapper.querySelector(".pcd-maze-canvas");
    const mazePath = JSON.parse(canvas.dataset.mazePath);
    const cellSize = Number(canvas.dataset.cellSize);

    const mazeWpx = Number(canvas.dataset.mazeWidth);
    const mazeHpx = Number(canvas.dataset.mazeHeight);

    console.log("[PCD] Init Captcha", { mazeWpx, mazeHpx, cellSize });

    const correctPoints = pcdConvertMazePathToPixelPath(mazePath, cellSize);
    const fake1 = pcdFakeMirror(correctPoints, mazeWpx);
    const fake2 = pcdFakeRotate90(correctPoints, mazeWpx);

    let all = [
        { pts: fake1, correct: false },
        { pts: fake2, correct: false },
        { pts: correctPoints, correct: true }
    ];

    // Shuffle
    for (let i = all.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [all[i], all[j]] = [all[j], all[i]];
    }

    const correctIndex = all.findIndex(item => item.correct);
    wrapper.dataset.correctPathId = String(correctIndex);

    console.log("[PCD] Pfad-Mix", {
        correctIndex,
        totalPaths: all.length
    });

    const list = wrapper.querySelector(".pcd-path-options");
    list.innerHTML = "";

    // PREVIEW unten → 3× kleiner
    const previewScale = 1 / 3;

    // Touch Detection
    const isTouch = window.matchMedia("(pointer: coarse)").matches;

    all.forEach((item, id) => {

        const div = document.createElement("div");
        div.className = "pcd-path";
        div.dataset.id = id;

        // ------------------------------------------------------------
        // TOUCH DEVICE → Tap-to-Select
        // ------------------------------------------------------------
        if (isTouch) {
            div.draggable = false;

            div.addEventListener("click", () => {
                const correctId = wrapper.dataset.correctPathId;
                const clickedId = div.dataset.id;
                const status = wrapper.querySelector(".pcd-labyrinth-status");
                const expected = wrapper.querySelector(".pcd-labyrinth-expected");
                const token = wrapper.querySelector(".pcd-labyrinth-token");

                if (clickedId === correctId) {
                    status.textContent = "Richtig!";
                    status.classList.add("pcd-status-ok");
                    status.classList.remove("pcd-status-error");

                    token.value = expected.value;
                } else {
                    status.textContent = "Falscher Pfad! Neues Labyrinth geladen.";
                    status.classList.add("pcd-status-error");
                    status.classList.remove("pcd-status-ok");

                    token.value = "";
                    pcdReloadCaptcha(wrapper);
                }
            });
        }

        // ------------------------------------------------------------
        // DESKTOP → Drag & Drop
        // ------------------------------------------------------------
        else {
            div.draggable = true;

            div.addEventListener("dragstart", e => {
                e.dataTransfer.setData("text/path-id", String(id));
                console.log("[PCD] dragstart Pfad", { id });

                // Originalgröße bestimmen
                const xs = item.pts.map(p => p[0]);
                const ys = item.pts.map(p => p[1]);
                const origW = Math.max(...xs) - Math.min(...xs);
                const origH = Math.max(...ys) - Math.min(...ys);

                // +20px für echte Darstellung
                const ghostW = origW + 20;
                const ghostH = origH + 20;

                const ghost = pcdCreatePathSVG(
                    item.pts,
                    ghostW,
                    ghostH,
                    "drag-ghost id=" + id
                );

                ghost.style.position = "absolute";
                ghost.style.top = "-1000px";
                ghost.style.left = "-1000px";
                ghost.style.pointerEvents = "none";

                document.body.appendChild(ghost);

                try {
                    e.dataTransfer.setDragImage(ghost, ghostW / 2, ghostH / 2);
                } catch (err) {
                    console.warn("[PCD] setDragImage Fehler", err);
                }

                const cleanup = () => {
                    ghost.remove();
                    div.removeEventListener("dragend", cleanup);
                };

                div.addEventListener("dragend", cleanup);
            });
        }

        // Punkte unten in kleiner Version rendern
        const scaledPoints = item.pts.map(([x, y]) => [
            x * previewScale,
            y * previewScale
        ]);

        const scaledW = mazeWpx * previewScale;
        const scaledH = mazeHpx * previewScale;

        div.appendChild(
            pcdCreatePathSVG(scaledPoints, scaledW, scaledH, "preview unten id=" + id)
        );

        list.appendChild(div);
    });

    // Drop-Logik wird vom Maze-Renderer übernommen
}

// ============================================================
// CF7 Fehler-Ausgabe
// ============================================================
document.addEventListener("wpcf7invalid", function (event) {
    const response = event.detail.apiResponse || {};
    const invalids = response.invalid_fields || [];

    console.log("[PCD] wpcf7invalid", response);

    const captchaError = invalids.find(f => f.field === "captcha");
    const form = event.target;

    const statusEl = form.querySelector(".pcd-path-captcha-wrapper .pcd-labyrinth-status");
    if (!statusEl) return;

    if (captchaError) {
        statusEl.textContent = captchaError.message || "Bitte Captcha lösen";
        statusEl.classList.add("pcd-status-error");
    } else {
        statusEl.textContent = "";
        statusEl.classList.remove("pcd-status-error");
    }
});

document.addEventListener("wpcf7mailsent", function (event) {
    const form = event.target;
    const statusEl = form.querySelector(".pcd-path-captcha-wrapper .pcd-labyrinth-status");
    if (statusEl) {
        statusEl.textContent = "";
        statusEl.classList.remove("pcd-status-error");
    }
    console.log("[PCD] wpcf7mailsent");
});

// ------------------------------------------------------------
// Init
// ------------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
    console.log("[PCD] DOMContentLoaded – Init Captcha");
    document.querySelectorAll(".pcd-path-captcha-wrapper").forEach(pcdInitCaptcha);
});
