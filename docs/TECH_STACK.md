# Tech Stack

This is the plan. Nothing is installed until Stage 2.

## Choices

| Area | Choice | Why |
|---|---|---|
| Engine | **Phaser 3** | Mature 2D web framework with scenes, Arcade physics, tilemaps, audio and input |
| Language | **JavaScript (ES modules)** | No compile step to learn; TypeScript can be added later |
| Build / dev server | **Vite** | Fast hot reload and a simple production build |
| Package manager | **npm** | Default with Node.js |
| Runtime | Node.js LTS (for tooling only) | The game itself runs in the browser |
| Hosting | **GitHub Pages** | Free static hosting from this repo |
| Level design (if needed) | Tiled map editor (JSON export) | Native Phaser tilemap support |

## Planned Directory Structure (Stage 2)

```
Amine-Game-Dev/
├── docs/               # Design docs and roadmap
├── public/
│   └── assets/         # Images, audio, tilemaps (served as-is)
│       ├── images/
│       ├── audio/
│       └── maps/
├── src/
│   ├── main.js         # Phaser game config and entry point
│   ├── scenes/         # Boot, Preload, MainMenu, Game, GameOver
│   ├── objects/        # Player, enemies, pickups (game entities)
│   └── utils/          # Shared helpers and constants
├── index.html
├── package.json
└── vite.config.js
```

## Conventions

- **One class per file.** File names match class names (`Player.js` → `class Player`).
- **Scenes** extend `Phaser.Scene` and use a string key matching the class name.
- **Constants** (sizes, speeds, colors) live in `src/utils/constants.js`, not as magic numbers.
- **Asset keys** are defined once in Preload and reused by name.
- **Commits:** small and descriptive (e.g. "Add player jump").
- **Branches:** one branch per stage or feature.

## Browser Targets

Latest Chrome, Firefox, Edge and Safari on desktop. Mobile is a stretch goal.
