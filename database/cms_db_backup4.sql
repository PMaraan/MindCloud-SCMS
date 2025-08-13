--
-- PostgreSQL database dump
--

-- Dumped from database version 17.5
-- Dumped by pg_dump version 17.5

-- Started on 2025-08-13 22:37:26

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 240 (class 1259 OID 18232)
-- Name: college_deans; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.college_deans (
    college_id integer NOT NULL,
    dean_id character(13) NOT NULL
);


ALTER TABLE public.college_deans OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 18014)
-- Name: colleges; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.colleges (
    college_id integer NOT NULL,
    short_name character varying(10) NOT NULL,
    college_name character varying(100) NOT NULL
);


ALTER TABLE public.colleges OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 18013)
-- Name: colleges_college_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.colleges_college_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.colleges_college_id_seq OWNER TO postgres;

--
-- TOC entry 5107 (class 0 OID 0)
-- Dependencies: 226
-- Name: colleges_college_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.colleges_college_id_seq OWNED BY public.colleges.college_id;


--
-- TOC entry 245 (class 1259 OID 18313)
-- Name: course_assignments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.course_assignments (
    assignment_id integer NOT NULL,
    professor_id character varying(13) NOT NULL,
    course_id integer NOT NULL,
    program_id integer NOT NULL,
    semester character varying(10),
    year integer,
    CONSTRAINT course_assignments_semester_check CHECK (((semester)::text = ANY ((ARRAY['1st'::character varying, '2nd'::character varying, 'Summer'::character varying])::text[])))
);


ALTER TABLE public.course_assignments OWNER TO postgres;

--
-- TOC entry 244 (class 1259 OID 18312)
-- Name: course_assignments_assignment_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.course_assignments_assignment_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.course_assignments_assignment_id_seq OWNER TO postgres;

--
-- TOC entry 5108 (class 0 OID 0)
-- Dependencies: 244
-- Name: course_assignments_assignment_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.course_assignments_assignment_id_seq OWNED BY public.course_assignments.assignment_id;


--
-- TOC entry 233 (class 1259 OID 18051)
-- Name: courses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.courses (
    course_id integer NOT NULL,
    course_code character varying(50) NOT NULL,
    course_name character varying(50) NOT NULL,
    college_id integer
);


ALTER TABLE public.courses OWNER TO postgres;

--
-- TOC entry 232 (class 1259 OID 18050)
-- Name: courses_course_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.courses_course_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.courses_course_id_seq OWNER TO postgres;

--
-- TOC entry 5109 (class 0 OID 0)
-- Dependencies: 232
-- Name: courses_course_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.courses_course_id_seq OWNED BY public.courses.course_id;


--
-- TOC entry 224 (class 1259 OID 16661)
-- Name: file_access_rule_conditions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.file_access_rule_conditions (
    rule_id integer NOT NULL,
    condition_type character varying(10) NOT NULL,
    condition_id integer NOT NULL,
    CONSTRAINT file_access_rule_conditions_condition_type_check CHECK (((condition_type)::text = ANY ((ARRAY['college'::character varying, 'role'::character varying])::text[])))
);


ALTER TABLE public.file_access_rule_conditions OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 16638)
-- Name: file_access_rules; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.file_access_rules (
    rule_id integer NOT NULL,
    file_id integer,
    permission_id integer,
    match_type character varying(3),
    created_by character(13),
    created_at timestamp without time zone DEFAULT now(),
    CONSTRAINT file_access_rules_match_type_check CHECK (((match_type)::text = ANY ((ARRAY['AND'::character varying, 'OR'::character varying])::text[])))
);


ALTER TABLE public.file_access_rules OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 16637)
-- Name: file_access_rules_rule_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.file_access_rules_rule_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.file_access_rules_rule_id_seq OWNER TO postgres;

--
-- TOC entry 5110 (class 0 OID 0)
-- Dependencies: 222
-- Name: file_access_rules_rule_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.file_access_rules_rule_id_seq OWNED BY public.file_access_rules.rule_id;


--
-- TOC entry 221 (class 1259 OID 16617)
-- Name: file_college_permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.file_college_permissions (
    file_id integer NOT NULL,
    college_id character varying(10) NOT NULL,
    permission_id integer NOT NULL
);


ALTER TABLE public.file_college_permissions OWNER TO postgres;

--
-- TOC entry 220 (class 1259 OID 16597)
-- Name: file_role_permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.file_role_permissions (
    file_id integer NOT NULL,
    role_id integer NOT NULL,
    permission_id integer NOT NULL
);


ALTER TABLE public.file_role_permissions OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 16577)
-- Name: file_user_permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.file_user_permissions (
    file_id integer NOT NULL,
    id_no character(13) NOT NULL,
    permission_id integer NOT NULL
);


ALTER TABLE public.file_user_permissions OWNER TO postgres;

--
-- TOC entry 218 (class 1259 OID 16565)
-- Name: files; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.files (
    file_id integer NOT NULL,
    name character varying(255) NOT NULL,
    uploaded_by character(13),
    uploaded_at timestamp without time zone DEFAULT now()
);


ALTER TABLE public.files OWNER TO postgres;

--
-- TOC entry 217 (class 1259 OID 16564)
-- Name: files_file_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.files_file_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.files_file_id_seq OWNER TO postgres;

--
-- TOC entry 5111 (class 0 OID 0)
-- Dependencies: 217
-- Name: files_file_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.files_file_id_seq OWNED BY public.files.file_id;


--
-- TOC entry 236 (class 1259 OID 18083)
-- Name: permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.permissions (
    permission_id integer NOT NULL,
    permission_name character varying(50) NOT NULL,
    category character varying(50) NOT NULL
);


ALTER TABLE public.permissions OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 18251)
-- Name: program_chairs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.program_chairs (
    program_id integer NOT NULL,
    chair_id character(13) NOT NULL
);


ALTER TABLE public.program_chairs OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 18035)
-- Name: programs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.programs (
    program_id integer NOT NULL,
    program_name character varying NOT NULL,
    college_id integer NOT NULL,
    chair character(13)
);


ALTER TABLE public.programs OWNER TO postgres;

--
-- TOC entry 230 (class 1259 OID 18034)
-- Name: programs_program_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.programs_program_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.programs_program_id_seq OWNER TO postgres;

--
-- TOC entry 5112 (class 0 OID 0)
-- Dependencies: 230
-- Name: programs_program_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.programs_program_id_seq OWNED BY public.programs.program_id;


--
-- TOC entry 239 (class 1259 OID 18140)
-- Name: role_grant_permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.role_grant_permissions (
    granter_role_id integer NOT NULL,
    grantee_role_id integer NOT NULL,
    permission_id integer NOT NULL
);


ALTER TABLE public.role_grant_permissions OWNER TO postgres;

--
-- TOC entry 238 (class 1259 OID 18108)
-- Name: role_permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.role_permissions (
    role_id integer NOT NULL,
    permission_id integer NOT NULL
);


ALTER TABLE public.role_permissions OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 18026)
-- Name: roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.roles (
    role_id integer NOT NULL,
    role_name character varying(50) NOT NULL,
    role_level integer NOT NULL
);


ALTER TABLE public.roles OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 18025)
-- Name: roles_role_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.roles_role_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_role_id_seq OWNER TO postgres;

--
-- TOC entry 5113 (class 0 OID 0)
-- Dependencies: 228
-- Name: roles_role_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.roles_role_id_seq OWNED BY public.roles.role_id;


--
-- TOC entry 235 (class 1259 OID 18063)
-- Name: syllabi; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.syllabi (
    syllabus_id integer NOT NULL,
    course_id integer NOT NULL,
    program_id integer NOT NULL,
    version character varying(10),
    content jsonb,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    status character varying(20),
    noted_by character varying(13),
    approved_by character varying(13)
);


ALTER TABLE public.syllabi OWNER TO postgres;

--
-- TOC entry 234 (class 1259 OID 18062)
-- Name: syllabi_syllabus_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.syllabi_syllabus_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.syllabi_syllabus_id_seq OWNER TO postgres;

--
-- TOC entry 5114 (class 0 OID 0)
-- Dependencies: 234
-- Name: syllabi_syllabus_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.syllabi_syllabus_id_seq OWNED BY public.syllabi.syllabus_id;


--
-- TOC entry 243 (class 1259 OID 18292)
-- Name: syllabus_editors; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.syllabus_editors (
    id integer NOT NULL,
    syllabus_id integer NOT NULL,
    editor_id character(13) NOT NULL,
    role character varying(20) DEFAULT 'editor'::character varying,
    assigned_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT syllabus_editors_role_check CHECK (((role)::text = ANY ((ARRAY['editor'::character varying, 'author'::character varying, 'reviewer'::character varying])::text[])))
);


ALTER TABLE public.syllabus_editors OWNER TO postgres;

--
-- TOC entry 242 (class 1259 OID 18291)
-- Name: syllabus_editors_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.syllabus_editors ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.syllabus_editors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 237 (class 1259 OID 18088)
-- Name: user_roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_roles (
    id_no character(13) NOT NULL,
    role_id integer NOT NULL,
    college_id integer
);


ALTER TABLE public.user_roles OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 18004)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id_no character(13) NOT NULL,
    fname character varying(100) NOT NULL,
    mname character varying(100),
    lname character varying(100) NOT NULL,
    email character varying(254) NOT NULL,
    password text NOT NULL
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 4830 (class 2604 OID 18017)
-- Name: colleges college_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.colleges ALTER COLUMN college_id SET DEFAULT nextval('public.colleges_college_id_seq'::regclass);


--
-- TOC entry 4839 (class 2604 OID 18316)
-- Name: course_assignments assignment_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.course_assignments ALTER COLUMN assignment_id SET DEFAULT nextval('public.course_assignments_assignment_id_seq'::regclass);


--
-- TOC entry 4833 (class 2604 OID 18054)
-- Name: courses course_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.courses ALTER COLUMN course_id SET DEFAULT nextval('public.courses_course_id_seq'::regclass);


--
-- TOC entry 4828 (class 2604 OID 16641)
-- Name: file_access_rules rule_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.file_access_rules ALTER COLUMN rule_id SET DEFAULT nextval('public.file_access_rules_rule_id_seq'::regclass);


--
-- TOC entry 4826 (class 2604 OID 16568)
-- Name: files file_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.files ALTER COLUMN file_id SET DEFAULT nextval('public.files_file_id_seq'::regclass);


--
-- TOC entry 4832 (class 2604 OID 18038)
-- Name: programs program_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.programs ALTER COLUMN program_id SET DEFAULT nextval('public.programs_program_id_seq'::regclass);


--
-- TOC entry 4831 (class 2604 OID 18029)
-- Name: roles role_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles ALTER COLUMN role_id SET DEFAULT nextval('public.roles_role_id_seq'::regclass);


--
-- TOC entry 4834 (class 2604 OID 18066)
-- Name: syllabi syllabus_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.syllabi ALTER COLUMN syllabus_id SET DEFAULT nextval('public.syllabi_syllabus_id_seq'::regclass);


--
-- TOC entry 5096 (class 0 OID 18232)
-- Dependencies: 240
-- Data for Name: college_deans; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.college_deans (college_id, dean_id) FROM stdin;
2	2025-01-40002
3	2025-01-40003
1	2025-01-50004
\.


--
-- TOC entry 5083 (class 0 OID 18014)
-- Dependencies: 227
-- Data for Name: colleges; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.colleges (college_id, short_name, college_name) FROM stdin;
2	CEA	College of Engineering
3	CLAE	College of Liberal Arts and Education
5	CFAD	College of Fine Arts and Design
4	CON	College of Nursing
1	CCS	College of Information Technology and Computer Science
\.


--
-- TOC entry 5101 (class 0 OID 18313)
-- Dependencies: 245
-- Data for Name: course_assignments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.course_assignments (assignment_id, professor_id, course_id, program_id, semester, year) FROM stdin;
\.


--
-- TOC entry 5089 (class 0 OID 18051)
-- Dependencies: 233
-- Data for Name: courses; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.courses (course_id, course_code, course_name, college_id) FROM stdin;
1	LVTN01G	Living in the IT Era	1
2	CCSN01C	Intro to Computing	1
3	BSIT01C	Game Development	1
4	BSCS01C	Artificial Intelligence	1
5	MATS01G	General Mathematics	2
6	CEAN01E	Math for Engineers	2
7	BSME01E	Kinematics	2
8	BSCE01E	Rigid Bodies	2
9	UTSN01G	Understanding the Self	3
10	CLAE01A	General Education	3
11	BSPS01A	Introduction to Psychology	3
12	BSGE01A	Secondary Education	3
\.


--
-- TOC entry 5080 (class 0 OID 16661)
-- Dependencies: 224
-- Data for Name: file_access_rule_conditions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.file_access_rule_conditions (rule_id, condition_type, condition_id) FROM stdin;
\.


--
-- TOC entry 5079 (class 0 OID 16638)
-- Dependencies: 223
-- Data for Name: file_access_rules; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.file_access_rules (rule_id, file_id, permission_id, match_type, created_by, created_at) FROM stdin;
\.


--
-- TOC entry 5077 (class 0 OID 16617)
-- Dependencies: 221
-- Data for Name: file_college_permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.file_college_permissions (file_id, college_id, permission_id) FROM stdin;
\.


--
-- TOC entry 5076 (class 0 OID 16597)
-- Dependencies: 220
-- Data for Name: file_role_permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.file_role_permissions (file_id, role_id, permission_id) FROM stdin;
\.


--
-- TOC entry 5075 (class 0 OID 16577)
-- Dependencies: 219
-- Data for Name: file_user_permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.file_user_permissions (file_id, id_no, permission_id) FROM stdin;
\.


--
-- TOC entry 5074 (class 0 OID 16565)
-- Dependencies: 218
-- Data for Name: files; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.files (file_id, name, uploaded_by, uploaded_at) FROM stdin;
\.


--
-- TOC entry 5092 (class 0 OID 18083)
-- Dependencies: 236
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.permissions (permission_id, permission_name, category) FROM stdin;
11	AccountCreation	Accounts
12	AccountViewing	Accounts
13	AccountModification	Accounts
14	AccountDeletion	Accounts
21	RoleCreation	Roles
22	RoleViewing	Roles
23	RoleModification	Roles
24	RoleDeletion	Roles
25	RoleAllocation	Roles
31	CollegeCreation	Colleges
32	CollegeViewing	Colleges
33	CollegeModification	Colleges
34	CollegeDeletion	Colleges
41	ProgramCreation	Programs
42	ProgramViewing	Programs
43	ProgramModification	Programs
44	ProgramDeletion	Programs
51	CourseCreation	Courses
52	CourseViewing	Courses
53	CourseModification	Courses
54	CourseDeletion	Courses
62	FacultyViewing	Faculty
63	FacultyModification	Faculty
71	SyllabusTemplateCreation	Templates
72	SyllabusTemplateViewing	Templates
73	SyllabusTemplateModification	Templates
74	SyllabusTemplateDeletion	Templates
81	SyllabusCreation	Syllabus
82	SyllabusViewing	Syllabus
83	SyllabusModification	Syllabus
84	SyllabusDeletion	Syllabus
85	SyllabusAllocation	Syllabus
\.


--
-- TOC entry 5097 (class 0 OID 18251)
-- Dependencies: 241
-- Data for Name: program_chairs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.program_chairs (program_id, chair_id) FROM stdin;
\.


--
-- TOC entry 5087 (class 0 OID 18035)
-- Dependencies: 231
-- Data for Name: programs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.programs (program_id, program_name, college_id, chair) FROM stdin;
3	Mechanical Engineering	2	2025-01-50001
4	Civil Engineering	2	2025-01-50003
5	Psychology	3	2025-01-50002
6	Secondary Education	3	2025-01-50004
2	Computer Science	1	\N
1	Information Technology	1	\N
\.


--
-- TOC entry 5095 (class 0 OID 18140)
-- Dependencies: 239
-- Data for Name: role_grant_permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.role_grant_permissions (granter_role_id, grantee_role_id, permission_id) FROM stdin;
\.


--
-- TOC entry 5094 (class 0 OID 18108)
-- Dependencies: 238
-- Data for Name: role_permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.role_permissions (role_id, permission_id) FROM stdin;
1	11
1	12
1	13
1	14
1	21
1	22
1	23
1	24
2	11
2	12
2	13
2	14
2	21
2	22
2	23
2	24
2	31
2	32
2	33
2	34
2	41
2	42
2	43
2	44
2	51
2	52
2	53
2	54
2	71
2	72
2	73
2	74
2	82
3	11
3	12
3	13
3	14
3	21
3	22
3	23
3	24
3	31
3	32
3	33
3	34
3	41
3	42
3	43
3	44
3	51
3	52
3	53
3	54
3	71
3	72
3	73
3	74
3	82
4	41
4	42
4	43
4	44
4	51
4	52
4	53
4	54
4	62
4	63
4	71
4	72
4	73
4	74
4	81
4	82
4	83
4	84
5	41
5	42
5	43
5	44
5	51
5	52
5	53
5	54
5	62
5	63
5	71
5	72
5	73
5	74
5	81
5	82
5	83
5	84
6	51
6	52
6	53
6	54
6	62
6	63
6	71
6	72
6	73
6	74
6	81
6	82
6	83
6	84
6	85
7	81
7	82
7	83
7	84
\.


--
-- TOC entry 5085 (class 0 OID 18026)
-- Dependencies: 229
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.roles (role_id, role_name, role_level) FROM stdin;
4	Dean	4
5	College Secretary	5
6	Chair	6
7	Professor	7
2	VPAA	2
3	VPAA Secretary	3
8	IAB	8
1	Admin	2
25	Librarian	7
\.


--
-- TOC entry 5091 (class 0 OID 18063)
-- Dependencies: 235
-- Data for Name: syllabi; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.syllabi (syllabus_id, course_id, program_id, version, content, created_at, updated_at, status, noted_by, approved_by) FROM stdin;
\.


--
-- TOC entry 5099 (class 0 OID 18292)
-- Dependencies: 243
-- Data for Name: syllabus_editors; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.syllabus_editors (id, syllabus_id, editor_id, role, assigned_at) FROM stdin;
\.


--
-- TOC entry 5093 (class 0 OID 18088)
-- Dependencies: 237
-- Data for Name: user_roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.user_roles (id_no, role_id, college_id) FROM stdin;
2025-01-10001	1	\N
2025-01-20001	2	\N
2025-01-30001	3	\N
2025-01-40002	4	2
2025-01-60002	4	\N
2025-01-40003	4	3
2025-01-40001	4	\N
2025-01-50004	4	1
2025-01-60001	7	1
2025-01-50002	6	2
2025-01-50001	6	1
2025-01-50003	6	1
2025-01-50005	6	1
2025-01-60003	4	\N
2025-01-60004	6	1
2025-01-70002	7	1
\.


--
-- TOC entry 5081 (class 0 OID 18004)
-- Dependencies: 225
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id_no, fname, mname, lname, email, password) FROM stdin;
2025-01-40003	User6		Dean	dean3@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$T00vWDc1MDFsOGxNQ054dA$+3VjhkapTe5RpHg8dRrJKZt7Dc0XjysxcDSnW5V/IlI
2025-01-60003	Lance Nigel		Morada	lancemorada@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$YzR5MVROcVI0TnpKN1d6cw$S/J9Ja0lc8waSwKptvQH26tQezI0gc1b+ImDctiGQdc
2025-01-60001	Cyril	F	Pastor	professor@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$ZExJUkRkOGFSbzI1UXJCdA$jOFDjUjCe6fgx6JnPg9xsC9kgTcVG0GqcnWboNgJzz
2025-01-60002	Russel		Domingo	russeldomingo@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$QmJjZkZoMkVXOEJwZGUwMw$wEkI3sQqkLlTr7dRXwxFVhGfUlQqd/qFbtEXFEbEA5E
2025-01-50003	Jack		Black	jackblack@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$M040eEMvd3JndVVqOXRYeQ$3EbOshW8MVsrwsBgG/A52Pk3sRvmuHdeHO7wnu87d3A
2025-01-50004	Alis	A	Alis	lisalisa1@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$SjhtWXFNSjRHUHE0YzZsNg$a1j3FUZClat0uoOJyXcRBg/UZJW4ubpjxW/nMwsYfbg
2025-01-50005	Robert		Lee	robertlee@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$aFZhVi50czhzaDZINlguMA$BuAknsT5v14oW13q/h6Ev4yusT/aMEVgNOW4ChsE9QU
2025-01-60004	Chair		Four	chair4@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$Mm13Q0FJeTZxL29Uc2FTMg$D9zFzeSt0MMYg7WGL0TJECvESXuTFeZrthMgCxp0m8w
2025-01-70002	Professor	B	Two	prof2@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$dDB3ZnV1VXBpWC9FN2gvQQ$uMFlcv3lefkhC7XMxLloMLqVR+jzEijH1eXvrsufGcc
2025-01-10001	User1	A	Admin	admin@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$S1VtWW1VbTEvRlpqcUdtbg$a083dLbRaiZweFREvmKp9kOK5Ty17h81Gu1fybx3JQI
2025-01-20001	User2	B	VPAA	vpaa@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$ZmdYcFlHQ1prL3h6dUp0ZA$3XUqgQC+RDMQknkyUXoZvG94EEU5SW3LOVNOsx0xr5k
2025-01-40001	User4		Dean	dean@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$d29BS3NweXVsQklBMVVVcg$WyD7cJKEi7qG3hsyVLr6Pqk+ul/yPOu+aMfQaCov7vo
2025-01-30001	User3	C	VPAASecretary	vsecretary@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$djdEdmd1Mk5sSmRxcEdCag$wwT/D8naT2I6a08Vug2MQaRWqvNxtN4YGm9YdtSoNWk
2025-01-40002	User5	R	Dean	dean2@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$ZGxCMERCUnJudHNDZWs2eg$uhg8k9x0QPNFs+D6xDToclKqPKCJpAJsX/tJAQtJR0k
2025-01-40004	User7		Dean	dean4@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$S0xZdmtxQzJFRG93enRuag$mB7QeWgvjByKC94fRzNCVX6iNLGcKwOhikLSSwyTJN4
2025-01-50002	John	A	Doe	chair2@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$UmN3S2d1eWFtTzZIWjl1Qw$HsSRAhqyjfRHoYA5LsUeRsQZjzQ4/NVBh6E/PSoVkRg
2025-01-50001	Prince	E	Caparaz	chair@lpunetwork.edu.ph	$argon2id$v=19$m=65536,t=4,p=1$VnNhYUJsZjdPUnNiOEFHQQ$2AN2hCPuGn8f3Rbk8r69NnrV86bZWWiCTE9yL5nfIQE
\.


--
-- TOC entry 5115 (class 0 OID 0)
-- Dependencies: 226
-- Name: colleges_college_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.colleges_college_id_seq', 5, true);


--
-- TOC entry 5116 (class 0 OID 0)
-- Dependencies: 244
-- Name: course_assignments_assignment_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.course_assignments_assignment_id_seq', 1, false);


--
-- TOC entry 5117 (class 0 OID 0)
-- Dependencies: 232
-- Name: courses_course_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.courses_course_id_seq', 12, true);


--
-- TOC entry 5118 (class 0 OID 0)
-- Dependencies: 222
-- Name: file_access_rules_rule_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.file_access_rules_rule_id_seq', 1, false);


--
-- TOC entry 5119 (class 0 OID 0)
-- Dependencies: 217
-- Name: files_file_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.files_file_id_seq', 1, false);


--
-- TOC entry 5120 (class 0 OID 0)
-- Dependencies: 230
-- Name: programs_program_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.programs_program_id_seq', 9, true);


--
-- TOC entry 5121 (class 0 OID 0)
-- Dependencies: 228
-- Name: roles_role_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.roles_role_id_seq', 25, true);


--
-- TOC entry 5122 (class 0 OID 0)
-- Dependencies: 234
-- Name: syllabi_syllabus_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.syllabi_syllabus_id_seq', 1, false);


--
-- TOC entry 5123 (class 0 OID 0)
-- Dependencies: 242
-- Name: syllabus_editors_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.syllabus_editors_id_seq', 1, false);


--
-- TOC entry 4855 (class 2606 OID 16666)
-- Name: file_access_rule_conditions file_access_rule_conditions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.file_access_rule_conditions
    ADD CONSTRAINT file_access_rule_conditions_pkey PRIMARY KEY (rule_id, condition_type, condition_id);


--
-- TOC entry 4853 (class 2606 OID 16645)
-- Name: file_access_rules file_access_rules_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.file_access_rules
    ADD CONSTRAINT file_access_rules_pkey PRIMARY KEY (rule_id);


--
-- TOC entry 4851 (class 2606 OID 16621)
-- Name: file_college_permissions file_college_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.file_college_permissions
    ADD CONSTRAINT file_college_permissions_pkey PRIMARY KEY (file_id, college_id, permission_id);


--
-- TOC entry 4849 (class 2606 OID 16601)
-- Name: file_role_permissions file_role_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.file_role_permissions
    ADD CONSTRAINT file_role_permissions_pkey PRIMARY KEY (file_id, role_id, permission_id);


--
-- TOC entry 4847 (class 2606 OID 16581)
-- Name: file_user_permissions file_user_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.file_user_permissions
    ADD CONSTRAINT file_user_permissions_pkey PRIMARY KEY (file_id, id_no, permission_id);


--
-- TOC entry 4845 (class 2606 OID 16571)
-- Name: files files_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.files
    ADD CONSTRAINT files_pkey PRIMARY KEY (file_id);


--
-- TOC entry 4883 (class 2606 OID 18236)
-- Name: college_deans pk_collegedeans; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.college_deans
    ADD CONSTRAINT pk_collegedeans PRIMARY KEY (college_id, dean_id);


--
-- TOC entry 4861 (class 2606 OID 18019)
-- Name: colleges pk_colleges; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.colleges
    ADD CONSTRAINT pk_colleges PRIMARY KEY (college_id);


--
-- TOC entry 4899 (class 2606 OID 18319)
-- Name: course_assignments pk_courseassigns; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.course_assignments
    ADD CONSTRAINT pk_courseassigns PRIMARY KEY (assignment_id);


--
-- TOC entry 4871 (class 2606 OID 18056)
-- Name: courses pk_courses_courseid; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.courses
    ADD CONSTRAINT pk_courses_courseid PRIMARY KEY (course_id);


--
-- TOC entry 4875 (class 2606 OID 18087)
-- Name: permissions pk_perms_permid; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT pk_perms_permid PRIMARY KEY (permission_id);


--
-- TOC entry 4889 (class 2606 OID 18255)
-- Name: program_chairs pk_programchairs; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.program_chairs
    ADD CONSTRAINT pk_programchairs PRIMARY KEY (program_id, chair_id);


--
-- TOC entry 4867 (class 2606 OID 18042)
-- Name: programs pk_programs_programid; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.programs
    ADD CONSTRAINT pk_programs_programid PRIMARY KEY (program_id);


--
-- TOC entry 4881 (class 2606 OID 18221)
-- Name: role_grant_permissions pk_rolegrantperm; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_grant_permissions
    ADD CONSTRAINT pk_rolegrantperm PRIMARY KEY (granter_role_id, grantee_role_id, permission_id);


--
-- TOC entry 4879 (class 2606 OID 18204)
-- Name: role_permissions pk_roleperms; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT pk_roleperms PRIMARY KEY (role_id, permission_id);


--
-- TOC entry 4863 (class 2606 OID 18031)
-- Name: roles pk_roles_roleid; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT pk_roles_roleid PRIMARY KEY (role_id);


--
-- TOC entry 4895 (class 2606 OID 18299)
-- Name: syllabus_editors pk_syleditors; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.syllabus_editors
    ADD CONSTRAINT pk_syleditors PRIMARY KEY (id);


--
-- TOC entry 4873 (class 2606 OID 18072)
-- Name: syllabi pk_syllabi_syllabus_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.syllabi
    ADD CONSTRAINT pk_syllabi_syllabus_id PRIMARY KEY (syllabus_id);


--
-- TOC entry 4877 (class 2606 OID 18092)
-- Name: user_roles pk_user_roles_id_no_role_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT pk_user_roles_id_no_role_id PRIMARY KEY (id_no, role_id);


--
-- TOC entry 4857 (class 2606 OID 18010)
-- Name: users pk_users_id_no; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT pk_users_id_no PRIMARY KEY (id_no);


--
-- TOC entry 4885 (class 2606 OID 18238)
-- Name: college_deans uq_collegedeans_collegeid; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.college_deans
    ADD CONSTRAINT uq_collegedeans_collegeid UNIQUE (college_id);


--
-- TOC entry 4887 (class 2606 OID 18240)
-- Name: college_deans uq_collegedeans_deanid; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.college_deans
    ADD CONSTRAINT uq_collegedeans_deanid UNIQUE (dean_id);


--
-- TOC entry 4901 (class 2606 OID 18321)
-- Name: course_assignments uq_course_assignment; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.course_assignments
    ADD CONSTRAINT uq_course_assignment UNIQUE (professor_id, course_id, program_id, semester, year);


--
-- TOC entry 4891 (class 2606 OID 18259)
-- Name: program_chairs uq_programchairs_chairid; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.program_chairs
    ADD CONSTRAINT uq_programchairs_chairid UNIQUE (chair_id);


--
-- TOC entry 4893 (class 2606 OID 18257)
-- Name: program_chairs uq_programchairs_programid; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.program_chairs
    ADD CONSTRAINT uq_programchairs_programid UNIQUE (program_id);


--
-- TOC entry 4869 (class 2606 OID 18044)
-- Name: programs uq_programs_chair; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.programs
    ADD CONSTRAINT uq_programs_chair UNIQUE (chair);


--
-- TOC entry 4865 (class 2606 OID 18033)
-- Name: roles uq_roles_rolename; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT uq_roles_rolename UNIQUE (role_name);


--
-- TOC entry 4897 (class 2606 OID 18301)
-- Name: syllabus_editors uq_syleditor; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.syllabus_editors
    ADD CONSTRAINT uq_syleditor UNIQUE (syllabus_id, editor_id, role);


--
-- TOC entry 4859 (class 2606 OID 18012)
-- Name: users uq_users_email; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT uq_users_email UNIQUE (email);


--
-- TOC entry 4906 (class 2606 OID 16667)
-- Name: file_access_rule_conditions file_access_rule_conditions_rule_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.file_access_rule_conditions
    ADD CONSTRAINT file_access_rule_conditions_rule_id_fkey FOREIGN KEY (rule_id) REFERENCES public.file_access_rules(rule_id);


--
-- TOC entry 4905 (class 2606 OID 16646)
-- Name: file_access_rules file_access_rules_file_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.file_access_rules
    ADD CONSTRAINT file_access_rules_file_id_fkey FOREIGN KEY (file_id) REFERENCES public.files(file_id);


--
-- TOC entry 4904 (class 2606 OID 16622)
-- Name: file_college_permissions file_college_permissions_file_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.file_college_permissions
    ADD CONSTRAINT file_college_permissions_file_id_fkey FOREIGN KEY (file_id) REFERENCES public.files(file_id);


--
-- TOC entry 4903 (class 2606 OID 16602)
-- Name: file_role_permissions file_role_permissions_file_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.file_role_permissions
    ADD CONSTRAINT file_role_permissions_file_id_fkey FOREIGN KEY (file_id) REFERENCES public.files(file_id);


--
-- TOC entry 4902 (class 2606 OID 16582)
-- Name: file_user_permissions file_user_permissions_file_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.file_user_permissions
    ADD CONSTRAINT file_user_permissions_file_id_fkey FOREIGN KEY (file_id) REFERENCES public.files(file_id);


--
-- TOC entry 4919 (class 2606 OID 18241)
-- Name: college_deans fk_collegedeans_collegeid_to_colleges; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.college_deans
    ADD CONSTRAINT fk_collegedeans_collegeid_to_colleges FOREIGN KEY (college_id) REFERENCES public.colleges(college_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4920 (class 2606 OID 18246)
-- Name: college_deans fk_collegedeans_deanid_to_users; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.college_deans
    ADD CONSTRAINT fk_collegedeans_deanid_to_users FOREIGN KEY (dean_id) REFERENCES public.users(id_no) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4925 (class 2606 OID 18327)
-- Name: course_assignments fk_courseassigns_courseid_to_courses; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.course_assignments
    ADD CONSTRAINT fk_courseassigns_courseid_to_courses FOREIGN KEY (course_id) REFERENCES public.courses(course_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4926 (class 2606 OID 18322)
-- Name: course_assignments fk_courseassigns_profid_to_users; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.course_assignments
    ADD CONSTRAINT fk_courseassigns_profid_to_users FOREIGN KEY (professor_id) REFERENCES public.users(id_no) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4927 (class 2606 OID 18332)
-- Name: course_assignments fk_courseassigns_programid_to_programs; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.course_assignments
    ADD CONSTRAINT fk_courseassigns_programid_to_programs FOREIGN KEY (program_id) REFERENCES public.programs(program_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4908 (class 2606 OID 18227)
-- Name: courses fk_courses_collegeid_to_colleges; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.courses
    ADD CONSTRAINT fk_courses_collegeid_to_colleges FOREIGN KEY (college_id) REFERENCES public.colleges(college_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- TOC entry 4921 (class 2606 OID 18265)
-- Name: program_chairs fk_programchairs_chairid_to_users; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.program_chairs
    ADD CONSTRAINT fk_programchairs_chairid_to_users FOREIGN KEY (chair_id) REFERENCES public.users(id_no) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4922 (class 2606 OID 18260)
-- Name: program_chairs fk_programchairs_programid_to_programs; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.program_chairs
    ADD CONSTRAINT fk_programchairs_programid_to_programs FOREIGN KEY (program_id) REFERENCES public.programs(program_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4907 (class 2606 OID 18045)
-- Name: programs fk_programs_collegeid_to_colleges; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.programs
    ADD CONSTRAINT fk_programs_collegeid_to_colleges FOREIGN KEY (college_id) REFERENCES public.colleges(college_id) ON UPDATE CASCADE;


--
-- TOC entry 4916 (class 2606 OID 18210)
-- Name: role_grant_permissions fk_rolegrantperm_grantee_to_roles; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_grant_permissions
    ADD CONSTRAINT fk_rolegrantperm_grantee_to_roles FOREIGN KEY (grantee_role_id) REFERENCES public.roles(role_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4917 (class 2606 OID 18205)
-- Name: role_grant_permissions fk_rolegrantperm_granter_to_roles; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_grant_permissions
    ADD CONSTRAINT fk_rolegrantperm_granter_to_roles FOREIGN KEY (granter_role_id) REFERENCES public.roles(role_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4918 (class 2606 OID 18215)
-- Name: role_grant_permissions fk_rolegrantperm_permid_to_permissions; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_grant_permissions
    ADD CONSTRAINT fk_rolegrantperm_permid_to_permissions FOREIGN KEY (permission_id) REFERENCES public.permissions(permission_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4914 (class 2606 OID 18193)
-- Name: role_permissions fk_roleperms_permid_to_perms; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT fk_roleperms_permid_to_perms FOREIGN KEY (permission_id) REFERENCES public.permissions(permission_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4915 (class 2606 OID 18198)
-- Name: role_permissions fk_roleperms_roleid_to_roles; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT fk_roleperms_roleid_to_roles FOREIGN KEY (role_id) REFERENCES public.roles(role_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4923 (class 2606 OID 18307)
-- Name: syllabus_editors fk_syleditors_editorid_to_users; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.syllabus_editors
    ADD CONSTRAINT fk_syleditors_editorid_to_users FOREIGN KEY (editor_id) REFERENCES public.users(id_no) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4924 (class 2606 OID 18302)
-- Name: syllabus_editors fk_syleditors_sylid_to_syllabus; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.syllabus_editors
    ADD CONSTRAINT fk_syleditors_sylid_to_syllabus FOREIGN KEY (syllabus_id) REFERENCES public.syllabi(syllabus_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4909 (class 2606 OID 18073)
-- Name: syllabi fk_syllabi_course_id_to_courses_course_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.syllabi
    ADD CONSTRAINT fk_syllabi_course_id_to_courses_course_id FOREIGN KEY (course_id) REFERENCES public.courses(course_id);


--
-- TOC entry 4910 (class 2606 OID 18078)
-- Name: syllabi fk_syllabi_program_id_to_programs_program_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.syllabi
    ADD CONSTRAINT fk_syllabi_program_id_to_programs_program_id FOREIGN KEY (program_id) REFERENCES public.programs(program_id);


--
-- TOC entry 4911 (class 2606 OID 18347)
-- Name: user_roles fk_user_roles_college_id_to_colleges_college_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT fk_user_roles_college_id_to_colleges_college_id FOREIGN KEY (college_id) REFERENCES public.colleges(college_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- TOC entry 4912 (class 2606 OID 18337)
-- Name: user_roles fk_user_roles_id_no_to_users_id_no; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT fk_user_roles_id_no_to_users_id_no FOREIGN KEY (id_no) REFERENCES public.users(id_no) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4913 (class 2606 OID 18342)
-- Name: user_roles fk_user_roles_role_id_to_roles_role_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT fk_user_roles_role_id_to_roles_role_id FOREIGN KEY (role_id) REFERENCES public.roles(role_id) ON UPDATE CASCADE ON DELETE CASCADE;


-- Completed on 2025-08-13 22:37:26

--
-- PostgreSQL database dump complete
--

