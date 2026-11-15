export type UserRole = 'admin' | 'instructor' | 'student';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    identity_number: string | null;
    phone: string | null;
    bio: string | null;
    avatar_url: string | null;
    is_active: boolean;
    role: UserRole;
    roles: UserRole[];
    permissions: string[];
}

export interface AppSettings {
    site_name: string;
    site_tagline: string;
    institution_name: string;
    site_logo_url: string | null;
    allow_registration: boolean;
}

export interface NotificationItem {
    id: string;
    data: {
        category?: string;
        title: string;
        message: string;
        url?: string;
        course_id?: number | null;
    };
    read_at: string | null;
    created_at?: string;
    created_at_human?: string;
}

export interface SharedPageProps {
    auth: {
        user: AuthUser | null;
    };
    appSettings: AppSettings;
    notifications: {
        unread_count: number;
        recent: NotificationItem[];
    };
    flash: {
        success: string | null;
        error: string | null;
        warning: string | null;
    };
    [key: string]: unknown;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

export interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    is_active: boolean;
    subjects_count?: number;
    courses_count?: number;
}

export interface Subject {
    id: number;
    category_id: number;
    code: string;
    name: string;
    description?: string | null;
    is_active: boolean;
    category?: { id: number; name: string };
    courses_count?: number;
}

export interface AcademicPeriod {
    id: number;
    name: string;
    code: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
    courses_count?: number;
}

export interface Material {
    id: number;
    lesson_id: number;
    course_id: number;
    title: string;
    type: 'pdf' | 'ppt' | 'docx' | 'image' | 'video' | 'link' | 'text';
    file_path?: string | null;
    file_name?: string | null;
    mime_type?: string | null;
    file_size?: number | null;
    video_url?: string | null;
    content?: string | null;
    sort_order: number;
    is_published: boolean;
    lesson?: { id: number; title: string; meeting_number: number };
}

export interface Lesson {
    id: number;
    course_id: number;
    title: string;
    description?: string | null;
    meeting_number: number;
    sort_order: number;
    is_published: boolean;
    materials?: Material[];
}

export interface Submission {
    id: number;
    assignment_id: number;
    student_id: number;
    text_answer?: string | null;
    file_path?: string | null;
    file_name?: string | null;
    status: 'submitted' | 'late' | 'graded';
    score?: number | string | null;
    feedback?: string | null;
    submitted_at: string;
    graded_at?: string | null;
    student?: { id: number; name: string; email: string; identity_number?: string | null };
}

export interface Assignment {
    id: number;
    course_id: number;
    lesson_id?: number | null;
    title: string;
    description: string;
    attachment_path?: string | null;
    attachment_name?: string | null;
    due_at?: string | null;
    max_score: number | string;
    submission_type: 'both' | 'file' | 'text';
    allow_late_submission: boolean;
    is_published: boolean;
    submissions_count?: number;
    submissions?: Submission[];
    course?: { id: number; title: string; code: string };
    lesson?: { id: number; title: string; meeting_number: number };
}

export interface QuestionOption {
    id?: number;
    option_text: string;
    is_correct?: boolean;
    sort_order?: number;
}

export interface Question {
    id: number;
    category_id?: number | null;
    subject_id?: number | null;
    course_id?: number | null;
    type: 'multiple_choice' | 'true_false' | 'essay';
    question_text: string;
    explanation?: string | null;
    points: number | string;
    difficulty: 'easy' | 'medium' | 'hard';
    options?: QuestionOption[];
    category?: { id: number; name: string };
    subject?: { id: number; name: string; code: string };
}

export interface QuizAttempt {
    id: number;
    quiz_id: number;
    student_id: number;
    attempt_number: number;
    status: 'in_progress' | 'submitted' | 'auto_submitted' | 'graded';
    started_at: string;
    expires_at: string;
    submitted_at?: string | null;
    objective_score: number | string;
    essay_score: number | string;
    total_score: number | string;
    percentage: number | string;
    is_passed: boolean;
    needs_manual_grading: boolean;
    student?: { id: number; name: string; email: string; identity_number?: string | null };
    answers?: Array<{
        id: number;
        question_id: number;
        selected_option_id?: number | null;
        essay_answer?: string | null;
        is_correct?: boolean | null;
        score_awarded?: number | string | null;
        feedback?: string | null;
        question?: { id: number; type: string; question_text: string; points: number | string };
    }>;
}

export interface Quiz {
    id: number;
    course_id: number;
    lesson_id?: number | null;
    title: string;
    description?: string | null;
    type: 'quiz' | 'exam';
    duration_minutes: number;
    starts_at?: string | null;
    ends_at?: string | null;
    max_attempts: number;
    passing_score: number | string;
    shuffle_questions: boolean;
    shuffle_options: boolean;
    show_results: boolean;
    show_explanations: boolean;
    is_published: boolean;
    questions_count?: number;
    attempts_count?: number;
    questions?: Question[];
    attempts?: QuizAttempt[];
}

export interface Course {
    id: number;
    category_id: number;
    subject_id?: number | null;
    academic_period_id?: number | null;
    instructor_id: number;
    title: string;
    slug: string;
    code: string;
    description?: string | null;
    status: 'draft' | 'published' | 'archived';
    assignment_weight: number;
    quiz_weight: number;
    exam_weight: number;
    min_completion_percentage: number;
    min_passing_score: number | string;
    certificate_enabled: boolean;
    category?: { id: number; name: string };
    subject?: { id: number; name: string; code: string };
    academic_period?: { id: number; name: string };
    instructor?: { id: number; name: string; email?: string };
    enrollments_count?: number;
    lessons_count?: number;
    assignments_count?: number;
    quizzes_count?: number;
    lessons?: Lesson[];
    assignments?: Assignment[];
    quizzes?: Quiz[];
    announcements?: Announcement[];
    enrollments?: Enrollment[];
}

export interface Enrollment {
    id: number;
    course_id: number;
    student_id: number;
    status: 'active' | 'completed' | 'dropped';
    progress_percentage: number | string;
    final_score?: number | string | null;
    enrolled_at: string;
    completed_at?: string | null;
    course?: Course;
    student?: { id: number; name: string; email: string; identity_number?: string | null };
}

export interface Announcement {
    id: number;
    course_id?: number | null;
    author_id: number;
    title: string;
    content: string;
    is_pinned: boolean;
    published_at: string;
    author?: { id: number; name: string };
    course?: { id: number; title: string; code: string };
}

export interface Grade {
    id: number;
    course_id: number;
    student_id: number;
    assignment_avg: number | string;
    quiz_avg: number | string;
    exam_avg: number | string;
    final_score: number | string;
    letter_grade: string | null;
    instructor_feedback?: string | null;
    course?: Course;
}

export interface Certificate {
    id: number;
    course_id: number;
    student_id: number;
    certificate_number: string;
    final_score: number | string;
    verification_hash: string;
    issued_at: string;
    course?: Course;
    student?: { id: number; name: string; identity_number?: string | null };
}
