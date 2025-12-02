// ============================================================
// PCD Labyrinth Captcha – Maze Renderer (Dropzone direkt im Maze)
// ============================================================

// ------------------------------------------------------------
// Maze Generator (DFS)
// ------------------------------------------------------------
function generateMaze(cols, rows) {
    const grid = [];

    for (let y = 0; y < rows; y++) {
        const row = [];
        for (let x = 0; x < cols; x++) {
            row.push({
                x,
                y,
                visited: false,
                walls: [true, true, true, true]
            });
        }
        grid.push(row);
    }

    function getUnvisitedNeighbors(cell) {
        const { x, y } = cell;
        const out = [];

        if (y > 0) out.push(grid[y - 1][x]);
        if (x < cols - 1) out.push(grid[y][x + 1]);
        if (y < rows - 1) out.push(grid[y + 1][x]);
        if (x > 0) out.push(grid[y][x - 1]);

        return out.filter(n => !n.visited);
    }

    function removeWalls(a, b) {
        const dx = b.x - a.x;
        const dy = b.y - a.y;

        if (dx === 1) { a.walls[1] = false; b.walls[3] = false; }
        if (dx === -1) { a.walls[3] = false; b.walls[1] = false; }
        if (dy === 1) { a.walls[2] = false; b.walls[0] = false; }
        if (dy === -1) { a.walls[0] = false; b.walls[2] = false; }
    }

    const start = grid[0][0];
    start.visited = true;

    const stack = [start];

    while (stack.length > 0) {
        const current = stack.pop();
        const unvisited = getUnvisitedNeighbors(current);

        if (unvisited.length > 0) {
            stack.push(current);
            const next = unvisited[Math.floor(Math.random() * (unvisited.length))];
            next.visited = true;
            removeWalls(current, next);
            stack.push(next);
        }
    }

    return grid;
}

// ------------------------------------------------------------
// Pfad extrahieren (DFS)
// ------------------------------------------------------------
function extractPath(grid, cols, rows) {
    const start = grid[0][0];
    const end = grid[rows - 1][cols - 1];
    const visited = new Set();

    function key(c) { return `${c.x}-${c.y}`; }

    function dfs(cell, path) {
        if (cell === end) return path;

        visited.add(key(cell));

        const dirs = [
            { dx: 0, dy: -1, w: 0 },
            { dx: 1, dy: 0, w: 1 },
            { dx: 0, dy: 1, w: 2 },
            { dx: -1, dy: 0, w: 3 }
        ];

        for (const dir of dirs) {
            if (!cell.walls[dir.w]) {

                const nx = cell.x + dir.dx;
                const ny = cell.y + dir.dy;

                if (nx >= 0 && nx < cols && ny >= 0 && ny < rows) {
                    const next = grid[ny][nx];

                    if (!visited.has(key(next))) {
                        const res = dfs(next, [...path, { x: next.x, y: next.y }]);
                        if (res) return res;
                    }
                }
            }
        }
        return null;
    }

    return dfs(start, [{ x: 0, y: 0 }]);
}

// ------------------------------------------------------------
// Maze Render
// ------------------------------------------------------------
function renderMaze(grid, cols, rows, cellSize) {
    const w = cols * cellSize;
    const h = rows * cellSize;

    let svg = `<svg class="pcd-maze-svg" width="${w}" height="${h}"
                 viewBox="0 0 ${w} ${h}" xmlns="http://www.w3.org/2000/svg">`;

    // Start (Grün)
    svg += `<rect x="2" y="2" width="${cellSize - 4}" height="${cellSize - 4}"
              fill="#27ae60" />`;

    // Endpunkt (Orange)
    svg += `<rect x="${(cols - 1) * cellSize + 2}" 
              y="${(rows - 1) * cellSize + 2}"
              width="${cellSize - 4}" height="${cellSize - 4}"
              fill="#ff9800" />`;

    // Maze-Wände
    svg += `<g stroke="#222" stroke-width="2" fill="none">`;

    for (let y = 0; y < rows; y++) {
        for (let x = 0; x < cols; x++) {
            const c = grid[y][x];
            const px = x * cellSize;
            const py = y * cellSize;

            if (c.walls[0]) svg += `<line x1="${px}" y1="${py}" x2="${px + cellSize}" y2="${py}" />`;
            if (c.walls[1]) svg += `<line x1="${px + cellSize}" y1="${py}" x2="${px + cellSize}" y2="${py + cellSize}" />`;
            if (c.walls[2]) svg += `<line x1="${px}" y1="${py + cellSize}" x2="${px + cellSize}" y2="${py + cellSize}" />`;
            if (c.walls[3]) svg += `<line x1="${px}" y1="${py}" x2="${px}" y2="${py + cellSize}" />`;
        }
    }

    svg += `</g></svg>`;
    return svg;
}

// ------------------------------------------------------------
// BEI FALSCH → Captcha neu laden
// ------------------------------------------------------------
function pcdReloadCaptcha(wrapper) {

    const mazeCanvas = wrapper.querySelector(".pcd-maze-canvas");
    mazeCanvas.innerHTML = "";

    const paths = wrapper.querySelector(".pcd-path-options");
    paths.innerHTML = "";

    const status = wrapper.querySelector(".pcd-labyrinth-status");
    status.textContent = "Falscher Pfad! Neues Labyrinth geladen!";
    status.classList.remove("pcd-status-ok", "pcd-status-error");

    setTimeout(() => {
        initMazeDisplay();
        if (typeof pcdInitCaptcha === "function") {
            pcdInitCaptcha(wrapper);
        }
    }, 200);
}

// ------------------------------------------------------------
// Maze Init (KORRIGIERT)
// ------------------------------------------------------------
function initMazeDisplay() {
    document.querySelectorAll(".pcd-maze-canvas").forEach(el => {

        const cols = 6;
        const rows = 6;
        const cellSize = 20;

        const grid = generateMaze(cols, rows);
        const solutionPath = extractPath(grid, cols, rows);

        const width = cols * cellSize;
        const height = rows * cellSize;

        el.dataset.mazeWidth = width;
        el.dataset.mazeHeight = height;
        el.dataset.cellSize = cellSize;
        el.dataset.mazePath = JSON.stringify(solutionPath);

        // ------------------------------------------------------------
        // FIX 1: Maze + Overlay-Layer einfügen
        // ------------------------------------------------------------
        el.innerHTML = `
            ${renderMaze(grid, cols, rows, cellSize)}
            <svg class="pcd-maze-path-layer"
                 width="${width}" height="${height}"
                 style="position:absolute; top:0; left:0; pointer-events:none;"></svg>
        `;

        const svg = el.querySelector(".pcd-maze-svg");

        // ------------------------------------------------------------
        // FIX 2: dragover auf Wrapper statt SVG
        // ------------------------------------------------------------
        el.addEventListener("dragover", e => {
            e.preventDefault();
            svg.style.outline = "3px dashed #2980b9";
        });

        svg.addEventListener("dragleave", () => {
            svg.style.outline = "none";
        });

        svg.addEventListener("drop", e => {
            e.preventDefault();
            svg.style.outline = "none";

            const wrapper = el.closest(".pcd-path-captcha-wrapper");
            const correctId = wrapper.dataset.correctPathId;
            const droppedId = e.dataTransfer.getData("text/path-id");

            const status = wrapper.querySelector(".pcd-labyrinth-status");

            const expected = wrapper.querySelector('.pcd-labyrinth-expected');
            const token    = wrapper.querySelector('.pcd-labyrinth-token');

            if (droppedId === correctId) {
                status.textContent = "Korrekt! Richtiger Pfad.";
                status.classList.remove("pcd-status-error");
                status.classList.add("pcd-status-ok");

                if (expected && token) {
                    token.value = expected.value;
                }

                return;
            }

            status.textContent = "Falscher Pfad! Neues Labyrinth geladen.";
            status.classList.remove("pcd-status-ok");
            status.classList.add("pcd-status-error");

            pcdReloadCaptcha(wrapper);
        });
    });
}

document.addEventListener("DOMContentLoaded", initMazeDisplay);
