---
name: NotebookLM Integration
description: Enables the agent to access and synchronize specifications directly from Google NotebookLM via the browser tool.
---

# NotebookLM Skill

This skill allows me (the AI agent) to maintain a live connection with your NotebookLM notebooks.
Wenn der User nach Code fragt, prüfe zuerst vorhandene Notizbücher in NotebookLM nach:
- Architekturvorgaben
- API-Dokumentationen
- Design-Richtlinien
Integriere dieses Wissen direkt in den generierten Code.

## Capabilities
- Read all notebooks associated with your account.
- Search within specific notebooks for project guidelines, style guides, and technical specifications.
- Synchronize external documentation into the local workspace for immediate use during coding tasks.

## Usage
When I need to verify a requirement:
1. I will use the `browser_subagent` to visit [NotebookLM](https://notebooklm.google.com/).
2. I will search for relevant keywords or open the notebook matching the current project context.
3. I will extract the findings and document them in `.agent/skills/notebooklm/resources/specifications.md`.

## Metadata
- **Account:** eiblgunter@gmail.com (Verified)
- **Primary Source:** [NotebookLM](https://notebooklm.google.com/)
