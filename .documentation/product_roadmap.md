# CiaoCV Features & Roadmap

## Current Features (Verified)

### 1. Dashboard (Employer/Admin)
- **Job & Campaign Management**:
    - **Postes**: centralized job definitions with customizable interview questions and recording limits.
    - **Affichages**: Campaign tracking per platform (LinkedIn, etc.) with view/application analytics.
    - **Search & Filters**: robust filtering by status (Active, Paused, Closed).
- **Candidate Evaluation**:
    - **Video Player**: Integrated player for reviewing candidate responses.
    - **Collaboration**: Comment timeline for team discussions on candidates.
    - **Rating**: 5-star rating system and "Favorite" flagging.
    - **Workflow Status**: Pipeline management (New, Reviewed, Shortlisted, Rejected).
- **Settings & Administration**:
    - **Team Management**: Role-based access (Admin vs Evaluator).
    - **Branding**: Custom brand color configuration.
    - **Communications**: Email template editor with dynamic variables (`{{nom_candidat}}`).
    - **Billing**: Plan management and invoice history.

### 2. Candidate Experience (Public Side)
- **Application Portal**:
    - Mobile-responsive landing page.
    - **Device Check**: Camera/Microphone validation steps.
    - **Video Recording**: Browser-based recording with retake capability.
    - **Direct Upload**: Secure, direct-to-cloud (R2) payload handling.

## Proposed New Features (Roadmap)

### 1. AI-Powered Workflow 🧠
- **Smart Question Generator**: Generate interview questions automatically based on the Job Title/Description.
- **Transcription & Summarization**: Auto-transcribe candidate videos and provide a 3-bullet summary of their key points.
- **Sentiment & Keyword Analysis**: Highlight confident responses and detect keywords (e.g., "Java", "Sales") in the transcript.

### 2. Advanced Evaluation & Collaboration 🤝
- **Structured Scorecards**: Replace the simple 5-star rating with customizable criteria (e.g., "Communication", "Technical Skills", "Cultural Fit") weighted for a final score.
- **@Mentions & Notifications**: Allow evaluators to tag colleagues in comments (`@alice Look at this answer!`) triggering email/push notifications.
- **Time-Stamped Comments**: Link comments to specific seconds in the video player for precise feedback.

### 3. Hiring Automation 🚀
- **Integrated Scheduling**: Add a "Book Interview" action that syncs with Google/Outlook calendars (or Calendly integration) for the next round.
- **Automated Pipelines**:
    - Auto-move candidates to "Rejected" if they don't meet specific criteria.
    - Auto-send "Thank You" emails upon submission (currently manual or simple).
- **Integrations**: Webhooks for Slack/Teams notifications when a new candidate applies.

### 4. Candidate Experience 2.0 ✨
- **Practice Mode**: A "sandbox" question where candidates can record and watch themselves back before the real interview starts.
- **Text & File Questions**: Allow mixing video responses with text inputs (e.g., "Link to Portfolio") or file uploads (Certifications).
- **White-Labeling**: specific subdomains (`jobs.company.com`) and full logo customization for Enterprise plans.

### 5. Security & Compliance 🛡️
- **Data Retention Policies**: Automated deletion of candidate data after a set period (GDPR/Law 25 compliance).
- **Audit Logs**: Detailed activity log of who viewed/downloaded candidate data (partially exists, enhance for export).
