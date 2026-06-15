# Product

## Register

product

## Users
Acneno is used by three primary user groups.

HR admins and operations staff are the power users. They work in long, dense sessions across attendance, leave approval, and expense reconciliation. They need to scan large employee datasets quickly, compare patterns across dates, and resolve operational exceptions without the interface slowing them down.

Owners and managers check in a few times a day for dashboards, approvals, and exception handling. They need glanceability, clear prioritization, and fast paths to whatever needs attention.

Employees use the self-service side in short, low-patience, mobile-heavy sessions for clock-in, leave requests, and expense submissions. Their context is often physically demanding and connectivity-constrained: warehouse floors, retail counters, field work, delivery routes, and lower-end Android devices.

## Product Purpose
Acneno is a multi-module SMB operations platform for Indonesian businesses, with HR as one of its strongest and most operationally critical modules. It helps teams track, approve, and report on people and money across HR, operations, approvals, and supporting admin workflows.

The product succeeds when repetitive administrative work becomes easier to scan, easier to trust, and faster to complete. The interface should help users notice patterns and exceptions at a glance while staying calm under dense, high-frequency usage.

## Brand Personality
Calm, confident, approachable.

The product should feel professional without becoming stiff, and warm without becoming playful to the point of losing credibility. It should feel suitable for founder-led and owner-operated SMBs who need serious operational software but do not want enterprise heaviness.

Reference direction:
- Linear: dense information with clarity and restraint
- Cal.com: warm, modern, friendly business software

## Anti-references
Avoid the feel of SAP, traditional Indonesian government portals, and generic Bootstrap admin templates.

Specifically avoid:
- grey, cramped, hostile enterprise bureaucracy
- loud purple gradients, heavy shadows, and generic chart-card dashboards
- the interchangeable “AI dashboard” aesthetic that looks templated rather than product-specific
- leaderboard or gamified HR visualizations that undermine user dignity, especially around attendance or performance

## Design Principles
1. Design for scanning under operational load.
Dense tables, status matrices, and approval surfaces must optimize recognition speed, pattern detection, and exception handling for power users.

2. Encode meaning beyond color.
Critical statuses must use shape, label, and structure in addition to color so the interface remains legible for color-vision-deficient users and under fast scanning conditions.

3. Feel local to Indonesian SMB workflows.
Attendance, leave, and scheduling flows must respect real workplace rhythms such as Friday prayer, Ramadan schedule shifts, and major leave periods like Idul Fitri rather than forcing them into generic status models.

4. Respect dignity in sensitive HR data.
The product must minimize unnecessary exposure of personal leave reasons, performance data, and other sensitive employee information. Trust is a product feature here.

5. Serve both office desks and rough mobile conditions.
Admin workflows can be dense and desktop-oriented, but employee-facing flows must remain clear, high-contrast, thumb-friendly, and resilient on lower-end Android devices with unstable connections.

## Accessibility & Inclusion
Default target: keyboard-usable, readable dense data surfaces, clear focus states, reduced-motion respect, and mobile-friendly employee flows.

Additional requirements:
- Attendance and status systems must not rely on color alone. Use a durable shape-plus-color vocabulary and validate it with color-vision-deficiency simulation.
- Attendance matrices must be implemented as real tables with proper row and column headers so screen reader users can navigate cell by cell. A companion list view is encouraged for easier linear reading.
- Inclusion must reflect Indonesian workplace realities, not only WCAG mechanics. The product should support religious and cultural scheduling needs such as Friday prayer accommodations, Ramadan hour shifts, and predictable major leave periods.
- Sensitive HR information must be access-scoped carefully. Sick leave or personal leave reasons should only be visible to the requester and their approvers. Performance data should not leak across teams or be visualized in humiliating ways.
- Employee mobile usage should assume one-handed interaction, direct sunlight, intermittent 3G connectivity, offline-first submission queues where critical, and a realistic low-end Android baseline such as Android 10 on 4GB RAM devices.
