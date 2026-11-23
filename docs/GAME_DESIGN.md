# Game Design Document

> **Status:** Draft (Stage 1). Fill in sections marked _TBD_ as decisions are made.
> **Platform:** Web browser (desktop first, mobile optional)
> **Engine:** Phaser 3 (see [TECH_STACK.md](TECH_STACK.md))

---

## 1. Vision / Pitch

**Working title:** _TBD_

**One-sentence pitch:** _TBD_ — "It's a ___ game where you ___ in order to ___."

Guiding questions:
- What feeling should the player have after a 5-minute session?
- What is the one thing this game does that makes it memorable?
- Who is the target player (casual, arcade fan, puzzle lover, kids)?

---

## 2. Genre Decision

The genre is not decided yet. The candidates below are scored for a solo developer's first Phaser project (★ = low, ★★★ = high).

| Genre | Scope / Effort | Phaser Fit | Content Needed | Notes |
|---|---|---|---|---|
| 2D Platformer | ★★ | ★★★ (Arcade physics, tilemaps) | Levels, tilesets, animations | Classic; level design takes the most time |
| Top-down Action / Shooter | ★★ | ★★★ | Enemies, weapons, arenas | Arena-based design keeps scope small |
| Puzzle (grid / logic) | ★ | ★★ | Puzzle levels | Little art needed; design-heavy |
| Arcade / Endless | ★ | ★★★ | Few assets, procedural spawns | Smallest scope, highest replayability |

**Decision:** _TBD_
**Reason:** _TBD_

Tip: pick the genre whose **core loop you can prototype in one weekend**.

---

## 3. Core Gameplay Loop

_TBD after the genre decision._

Template:
1. **Action:** what the player does moment to moment (e.g. move, jump, shoot, place)
2. **Challenge:** what pushes back (enemies, timer, puzzle constraints)
3. **Reward:** what the player gets (score, progress, unlocks)
4. **Repeat / escalate:** how difficulty or variety grows

---

## 4. Controls

| Input | Desktop | Mobile (optional) |
|---|---|---|
| Move | _TBD_ (e.g. Arrow keys / WASD) | _TBD_ |
| Primary action | _TBD_ | _TBD_ |
| Pause / menu | Esc / P | On-screen button |

Rule: keep the core controls to **at most 3 inputs** for v1.

---

## 5. Player Goals and Progression

- **Short-term goal** (seconds): _TBD_
- **Mid-term goal** (a level or run): _TBD_
- **Long-term goal** (whole game): _TBD_
- **Win condition:** _TBD_
- **Lose condition:** _TBD_
- **Progression:** levels, score or high score, unlocks? _TBD_

---

## 6. Art Direction

- **Style:** _TBD_ (pixel art, flat vector, minimal shapes)
- **Base resolution:** 960×540 (16:9), scaled to fit the window
  - For pixel art, use a smaller base (e.g. 480×270 or 320×180) with pixel-perfect scaling
- **Color palette:** _TBD_ (choose 5–8 colors early)
- **Asset sources:** own artwork, or CC0 / properly licensed packs (license tracked in `assets/CREDITS.md`)

## 7. Audio Direction

- **Music:** _TBD_ (looping background track per scene)
- **SFX:** jump/shoot/hit/pickup/UI clicks, as needed by the genre
- **Sources:** own recordings, sfxr-style generators, or CC0 libraries (credited)
- Respect browser autoplay rules: start audio only after the first user input

---

## 8. Screens / Scenes Flow

```
Boot → Preload → Main Menu → Game ⇄ Pause
                     ↑          ↓
                     └── Game Over / Win
```

---

## 9. Scope Rules for v1

**In scope**
- A single, polished core loop
- Main menu, game screen, pause, game over / win screen
- Basic sound and music
- Keyboard controls on desktop browsers

**Out of scope (for v1)**
- Multiplayer / networking
- Accounts, online leaderboards, backend services
- In-app purchases or ads
- Level editor
- Story cutscenes and dialogue systems
- Full mobile and touch support (stretch goal only)

---

## 10. Open Questions

- [ ] Which genre? (see section 2)
- [ ] Working title?
- [ ] Art style and resolution?
- [ ] Is mobile/touch support required for v1?
