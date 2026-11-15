# DESIGN.md - VerdantLMS Design Direction

## Identity & Character
- **Product:** VerdantLMS - Smart Learning Management System for institutions, instructors, and students.
- **Voice & Tone:** Focused, academic, calm, and high-trust. Every screen prioritizes the user's primary decision (admin oversight, instructor grading/authoring, student learning/exam completion).
- **Dials:** `ENERGY 2 / RHYTHM 2 / MOTION 1`
  - **ENERGY 2 (Balanced):** Bright white & soft light-gray surfaces anchored by crisp royal-cobalt / periwinkle blue accents, defaulting to Light Mode unless toggled by the user.
  - **RHYTHM 2 (Consistent with purposeful breaks):** Workspace shell stays predictable; dashboard views vary layout by role workflow.
  - **MOTION 1 (Calm):** Subtle hover/focus transitions and drawer slides only (`prefers-reduced-motion` respected).

## Color System (Bright White & Light Gray + Royal Blue Accents; Soft Slate Dark Mode)
- **Light Mode (Default):**
  - App Canvas `#F8FAFC` (Alabaster Light Gray), Cards & Sidebar `#FFFFFF` (Pure White), Borders `#E2E8F0`, Primary Ink `#1E293B` (13.98:1 on `#F8FAFC` PASS), Muted Ink `#475569` (7.24:1 PASS).
- **Dark Mode (User-Toggleable):**
  - App Canvas `#1E293B` (Soft Dark Slate), Elevated Cards & Sidebar `#283548`, Recessed `#162030`, Borders `#3E4F69`, Primary Ink `#F8FAFC` (11.86:1 on `#283548` PASS), Muted Ink `#CBD5E1` (8.35:1 PASS).
- **Focused Blue Accents (`#1B2CC1` / `#7692FF` / `#ABD2FA`):**
  - `#1B2CC1` - Primary CTA & active sidebar item in Light Mode (`#FFFFFF` text: 9.65:1 PASS; `#1B2CC1` on `#F8FAFC`: 9.22:1 PASS), light chart bars.
  - `#7692FF` - Focus rings, progress indicators, dark-mode chart series.
  - `#ABD2FA` - Primary CTA & active sidebar item in Dark Mode (`#1E293B` text: 9.29:1 PASS), sky status badges.

## Typography & Radius
- **Font Family:** `Plus Jakarta Sans` and `JetBrains Mono` (class join codes, exam timers, certificate serial numbers).
- **Radii:** `6px` inputs/badges, `10px` buttons/nav items, `14px` cards/modals.
