# Roadmap

The project is built in stages. Each stage has goals, a checklist, and "done when" criteria. Only move to the next stage once the current one is done.

**Current stage:** Stage 1: Design and Roadmap

---

## Stage 1: Design and Roadmap

**Goal:** Decide what we are building and how, before writing code.

- [x] Create the game design document template ([GAME_DESIGN.md](GAME_DESIGN.md))
- [x] Define the tech stack ([TECH_STACK.md](TECH_STACK.md))
- [x] Write this roadmap
- [ ] Choose a genre (GAME_DESIGN.md §2)
- [ ] Write the pitch, core loop, controls and win/lose conditions
- [ ] Choose an art style and resolution

**Done when:** every _TBD_ in GAME_DESIGN.md sections 1–6 is filled in.

---

## Stage 2: Project Scaffold

**Goal:** An empty but running Phaser project.

- [ ] Initialize an npm project with Vite and Phaser 3
- [ ] Create the folder layout (`src/scenes`, `src/objects`, `assets/`)
- [ ] Add the `Boot`, `Preload`, `MainMenu` and `Game` scenes
- [ ] Set up scaling (fit to window, base resolution from the design doc)
- [ ] Add `npm run dev` and `npm run build` scripts
- [ ] Add `.gitignore` and update the README with run instructions

**Done when:** `npm run dev` opens a browser with a menu that switches to a blank game scene.

---

## Stage 3: Core-Loop Prototype

**Goal:** Prove that the game is fun with placeholder graphics.

- [ ] Player object with controls
- [ ] One core mechanic (the genre's main action)
- [ ] One challenge (enemy, hazard, timer or puzzle rule)
- [ ] Score or progress tracking
- [ ] Win and lose conditions with a Game Over / Win scene
- [ ] Restart flow

**Done when:** someone can play a full round from start to game over and wants to retry.

---

## Stage 4: Content and Polish

**Goal:** Turn the prototype into a real game.

- [ ] Replace placeholders with final art and animations
- [ ] Add music and sound effects
- [ ] Build levels or difficulty progression
- [ ] UI and HUD: score, lives/health, pause menu
- [ ] Juice: screen shake, particles, tweens, hit feedback
- [ ] Save the high score locally (localStorage)
- [ ] Credits file for third-party assets

**Done when:** the game feels complete for its v1 scope (GAME_DESIGN.md §9).

---

## Stage 5: Release

**Goal:** Ship it.

- [ ] Production build (`npm run build`)
- [ ] Deploy to GitHub Pages
- [ ] Test in Chrome, Firefox and Safari
- [ ] Playtest with at least 3 people and fix the top issues
- [ ] Final README: description, screenshots, play link, controls

**Done when:** the game is publicly playable via a link.
