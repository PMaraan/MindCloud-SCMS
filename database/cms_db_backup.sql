PGDMP  &                    }            cms_db    17.5    17.5 W    �           0    0    ENCODING    ENCODING        SET client_encoding = 'UTF8';
                           false            �           0    0 
   STDSTRINGS 
   STDSTRINGS     (   SET standard_conforming_strings = 'on';
                           false            �           0    0 
   SEARCHPATH 
   SEARCHPATH     8   SELECT pg_catalog.set_config('search_path', '', false);
                           false            �           1262    16388    cms_db    DATABASE     �   CREATE DATABASE cms_db WITH TEMPLATE = template0 ENCODING = 'UTF8' LOCALE_PROVIDER = libc LOCALE = 'English_United States.1252';
    DROP DATABASE cms_db;
                     postgres    false            �            1259    16490    colleges    TABLE     z   CREATE TABLE public.colleges (
    college_id character varying(10) NOT NULL,
    name character varying(100) NOT NULL
);
    DROP TABLE public.colleges;
       public         heap r       postgres    false            �            1259    16661    file_access_rule_conditions    TABLE     [  CREATE TABLE public.file_access_rule_conditions (
    rule_id integer NOT NULL,
    condition_type character varying(10) NOT NULL,
    condition_id integer NOT NULL,
    CONSTRAINT file_access_rule_conditions_condition_type_check CHECK (((condition_type)::text = ANY ((ARRAY['college'::character varying, 'role'::character varying])::text[])))
);
 /   DROP TABLE public.file_access_rule_conditions;
       public         heap r       postgres    false            �            1259    16638    file_access_rules    TABLE     �  CREATE TABLE public.file_access_rules (
    rule_id integer NOT NULL,
    file_id integer,
    permission_id integer,
    match_type character varying(3),
    created_by character(13),
    created_at timestamp without time zone DEFAULT now(),
    CONSTRAINT file_access_rules_match_type_check CHECK (((match_type)::text = ANY ((ARRAY['AND'::character varying, 'OR'::character varying])::text[])))
);
 %   DROP TABLE public.file_access_rules;
       public         heap r       postgres    false            �            1259    16637    file_access_rules_rule_id_seq    SEQUENCE     �   CREATE SEQUENCE public.file_access_rules_rule_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
 4   DROP SEQUENCE public.file_access_rules_rule_id_seq;
       public               postgres    false    232            �           0    0    file_access_rules_rule_id_seq    SEQUENCE OWNED BY     _   ALTER SEQUENCE public.file_access_rules_rule_id_seq OWNED BY public.file_access_rules.rule_id;
          public               postgres    false    231            �            1259    16617    file_college_permissions    TABLE     �   CREATE TABLE public.file_college_permissions (
    file_id integer NOT NULL,
    college_id character varying(10) NOT NULL,
    permission_id integer NOT NULL
);
 ,   DROP TABLE public.file_college_permissions;
       public         heap r       postgres    false            �            1259    16597    file_role_permissions    TABLE     �   CREATE TABLE public.file_role_permissions (
    file_id integer NOT NULL,
    role_id integer NOT NULL,
    permission_id integer NOT NULL
);
 )   DROP TABLE public.file_role_permissions;
       public         heap r       postgres    false            �            1259    16577    file_user_permissions    TABLE     �   CREATE TABLE public.file_user_permissions (
    file_id integer NOT NULL,
    id_no character(13) NOT NULL,
    permission_id integer NOT NULL
);
 )   DROP TABLE public.file_user_permissions;
       public         heap r       postgres    false            �            1259    16565    files    TABLE     �   CREATE TABLE public.files (
    file_id integer NOT NULL,
    name character varying(255) NOT NULL,
    uploaded_by character(13),
    uploaded_at timestamp without time zone DEFAULT now()
);
    DROP TABLE public.files;
       public         heap r       postgres    false            �            1259    16564    files_file_id_seq    SEQUENCE     �   CREATE SEQUENCE public.files_file_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
 (   DROP SEQUENCE public.files_file_id_seq;
       public               postgres    false    227            �           0    0    files_file_id_seq    SEQUENCE OWNED BY     G   ALTER SEQUENCE public.files_file_id_seq OWNED BY public.files.file_id;
          public               postgres    false    226            �            1259    16503    permissions    TABLE     �   CREATE TABLE public.permissions (
    permission_id integer NOT NULL,
    name character varying(50) NOT NULL,
    category character varying
);
    DROP TABLE public.permissions;
       public         heap r       postgres    false            �            1259    16502    permissions_permission_id_seq    SEQUENCE     �   CREATE SEQUENCE public.permissions_permission_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
 4   DROP SEQUENCE public.permissions_permission_id_seq;
       public               postgres    false    222            �           0    0    permissions_permission_id_seq    SEQUENCE OWNED BY     _   ALTER SEQUENCE public.permissions_permission_id_seq OWNED BY public.permissions.permission_id;
          public               postgres    false    221            �            1259    16683    programs    TABLE     �   CREATE TABLE public.programs (
    program_id integer NOT NULL,
    program_name character varying NOT NULL,
    college_id character varying(10) NOT NULL
);
    DROP TABLE public.programs;
       public         heap r       postgres    false            �            1259    16544    role_grant_permissions    TABLE     �   CREATE TABLE public.role_grant_permissions (
    granter_role_id integer NOT NULL,
    grantee_role_id integer NOT NULL,
    permission_id integer NOT NULL
);
 *   DROP TABLE public.role_grant_permissions;
       public         heap r       postgres    false            �            1259    16529    role_permissions    TABLE     k   CREATE TABLE public.role_permissions (
    role_id integer NOT NULL,
    permission_id integer NOT NULL
);
 $   DROP TABLE public.role_permissions;
       public         heap r       postgres    false            �            1259    16496    roles    TABLE     �   CREATE TABLE public.roles (
    role_id integer NOT NULL,
    name character varying(50) NOT NULL,
    level integer NOT NULL
);
    DROP TABLE public.roles;
       public         heap r       postgres    false            �            1259    16495    roles_role_id_seq    SEQUENCE     �   CREATE SEQUENCE public.roles_role_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
 (   DROP SEQUENCE public.roles_role_id_seq;
       public               postgres    false    220            �           0    0    roles_role_id_seq    SEQUENCE OWNED BY     G   ALTER SEQUENCE public.roles_role_id_seq OWNED BY public.roles.role_id;
          public               postgres    false    219            �            1259    16509 
   user_roles    TABLE     �   CREATE TABLE public.user_roles (
    id_no character(13) NOT NULL,
    role_id integer NOT NULL,
    college_id character varying(10)
);
    DROP TABLE public.user_roles;
       public         heap r       postgres    false            �            1259    16389    users    TABLE     �   CREATE TABLE public.users (
    id_no character(13) NOT NULL,
    fname character varying(100) NOT NULL,
    mname character varying(100),
    lname character varying(100) NOT NULL,
    email character varying(254) NOT NULL,
    password text NOT NULL
);
    DROP TABLE public.users;
       public         heap r       postgres    false            �           2604    16641    file_access_rules rule_id    DEFAULT     �   ALTER TABLE ONLY public.file_access_rules ALTER COLUMN rule_id SET DEFAULT nextval('public.file_access_rules_rule_id_seq'::regclass);
 H   ALTER TABLE public.file_access_rules ALTER COLUMN rule_id DROP DEFAULT;
       public               postgres    false    232    231    232            �           2604    16568    files file_id    DEFAULT     n   ALTER TABLE ONLY public.files ALTER COLUMN file_id SET DEFAULT nextval('public.files_file_id_seq'::regclass);
 <   ALTER TABLE public.files ALTER COLUMN file_id DROP DEFAULT;
       public               postgres    false    226    227    227            �           2604    16506    permissions permission_id    DEFAULT     �   ALTER TABLE ONLY public.permissions ALTER COLUMN permission_id SET DEFAULT nextval('public.permissions_permission_id_seq'::regclass);
 H   ALTER TABLE public.permissions ALTER COLUMN permission_id DROP DEFAULT;
       public               postgres    false    222    221    222            �           2604    16499    roles role_id    DEFAULT     n   ALTER TABLE ONLY public.roles ALTER COLUMN role_id SET DEFAULT nextval('public.roles_role_id_seq'::regclass);
 <   ALTER TABLE public.roles ALTER COLUMN role_id DROP DEFAULT;
       public               postgres    false    219    220    220            �          0    16490    colleges 
   TABLE DATA           4   COPY public.colleges (college_id, name) FROM stdin;
    public               postgres    false    218   Qw       �          0    16661    file_access_rule_conditions 
   TABLE DATA           \   COPY public.file_access_rule_conditions (rule_id, condition_type, condition_id) FROM stdin;
    public               postgres    false    233   �w       �          0    16638    file_access_rules 
   TABLE DATA           p   COPY public.file_access_rules (rule_id, file_id, permission_id, match_type, created_by, created_at) FROM stdin;
    public               postgres    false    232   �w       �          0    16617    file_college_permissions 
   TABLE DATA           V   COPY public.file_college_permissions (file_id, college_id, permission_id) FROM stdin;
    public               postgres    false    230   �w       �          0    16597    file_role_permissions 
   TABLE DATA           P   COPY public.file_role_permissions (file_id, role_id, permission_id) FROM stdin;
    public               postgres    false    229   x       �          0    16577    file_user_permissions 
   TABLE DATA           N   COPY public.file_user_permissions (file_id, id_no, permission_id) FROM stdin;
    public               postgres    false    228   3x       �          0    16565    files 
   TABLE DATA           H   COPY public.files (file_id, name, uploaded_by, uploaded_at) FROM stdin;
    public               postgres    false    227   Px       �          0    16503    permissions 
   TABLE DATA           D   COPY public.permissions (permission_id, name, category) FROM stdin;
    public               postgres    false    222   mx       �          0    16683    programs 
   TABLE DATA           H   COPY public.programs (program_id, program_name, college_id) FROM stdin;
    public               postgres    false    234   Uy       �          0    16544    role_grant_permissions 
   TABLE DATA           a   COPY public.role_grant_permissions (granter_role_id, grantee_role_id, permission_id) FROM stdin;
    public               postgres    false    225   �y       �          0    16529    role_permissions 
   TABLE DATA           B   COPY public.role_permissions (role_id, permission_id) FROM stdin;
    public               postgres    false    224   �y       �          0    16496    roles 
   TABLE DATA           5   COPY public.roles (role_id, name, level) FROM stdin;
    public               postgres    false    220   �z       �          0    16509 
   user_roles 
   TABLE DATA           @   COPY public.user_roles (id_no, role_id, college_id) FROM stdin;
    public               postgres    false    223   �z       �          0    16389    users 
   TABLE DATA           L   COPY public.users (id_no, fname, mname, lname, email, password) FROM stdin;
    public               postgres    false    217   3{       �           0    0    file_access_rules_rule_id_seq    SEQUENCE SET     L   SELECT pg_catalog.setval('public.file_access_rules_rule_id_seq', 1, false);
          public               postgres    false    231            �           0    0    files_file_id_seq    SEQUENCE SET     @   SELECT pg_catalog.setval('public.files_file_id_seq', 1, false);
          public               postgres    false    226            �           0    0    permissions_permission_id_seq    SEQUENCE SET     L   SELECT pg_catalog.setval('public.permissions_permission_id_seq', 1, false);
          public               postgres    false    221            �           0    0    roles_role_id_seq    SEQUENCE SET     @   SELECT pg_catalog.setval('public.roles_role_id_seq', 1, false);
          public               postgres    false    219            �           2606    16494    colleges colleges_pkey 
   CONSTRAINT     \   ALTER TABLE ONLY public.colleges
    ADD CONSTRAINT colleges_pkey PRIMARY KEY (college_id);
 @   ALTER TABLE ONLY public.colleges DROP CONSTRAINT colleges_pkey;
       public                 postgres    false    218            �           2606    16666 <   file_access_rule_conditions file_access_rule_conditions_pkey 
   CONSTRAINT     �   ALTER TABLE ONLY public.file_access_rule_conditions
    ADD CONSTRAINT file_access_rule_conditions_pkey PRIMARY KEY (rule_id, condition_type, condition_id);
 f   ALTER TABLE ONLY public.file_access_rule_conditions DROP CONSTRAINT file_access_rule_conditions_pkey;
       public                 postgres    false    233    233    233            �           2606    16645 (   file_access_rules file_access_rules_pkey 
   CONSTRAINT     k   ALTER TABLE ONLY public.file_access_rules
    ADD CONSTRAINT file_access_rules_pkey PRIMARY KEY (rule_id);
 R   ALTER TABLE ONLY public.file_access_rules DROP CONSTRAINT file_access_rules_pkey;
       public                 postgres    false    232            �           2606    16621 6   file_college_permissions file_college_permissions_pkey 
   CONSTRAINT     �   ALTER TABLE ONLY public.file_college_permissions
    ADD CONSTRAINT file_college_permissions_pkey PRIMARY KEY (file_id, college_id, permission_id);
 `   ALTER TABLE ONLY public.file_college_permissions DROP CONSTRAINT file_college_permissions_pkey;
       public                 postgres    false    230    230    230            �           2606    16601 0   file_role_permissions file_role_permissions_pkey 
   CONSTRAINT     �   ALTER TABLE ONLY public.file_role_permissions
    ADD CONSTRAINT file_role_permissions_pkey PRIMARY KEY (file_id, role_id, permission_id);
 Z   ALTER TABLE ONLY public.file_role_permissions DROP CONSTRAINT file_role_permissions_pkey;
       public                 postgres    false    229    229    229            �           2606    16581 0   file_user_permissions file_user_permissions_pkey 
   CONSTRAINT     �   ALTER TABLE ONLY public.file_user_permissions
    ADD CONSTRAINT file_user_permissions_pkey PRIMARY KEY (file_id, id_no, permission_id);
 Z   ALTER TABLE ONLY public.file_user_permissions DROP CONSTRAINT file_user_permissions_pkey;
       public                 postgres    false    228    228    228            �           2606    16571    files files_pkey 
   CONSTRAINT     S   ALTER TABLE ONLY public.files
    ADD CONSTRAINT files_pkey PRIMARY KEY (file_id);
 :   ALTER TABLE ONLY public.files DROP CONSTRAINT files_pkey;
       public                 postgres    false    227            �           2606    16508    permissions permissions_pkey 
   CONSTRAINT     e   ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (permission_id);
 F   ALTER TABLE ONLY public.permissions DROP CONSTRAINT permissions_pkey;
       public                 postgres    false    222            �           2606    16689    programs programs_pkey 
   CONSTRAINT     \   ALTER TABLE ONLY public.programs
    ADD CONSTRAINT programs_pkey PRIMARY KEY (program_id);
 @   ALTER TABLE ONLY public.programs DROP CONSTRAINT programs_pkey;
       public                 postgres    false    234            �           2606    16548 2   role_grant_permissions role_grant_permissions_pkey 
   CONSTRAINT     �   ALTER TABLE ONLY public.role_grant_permissions
    ADD CONSTRAINT role_grant_permissions_pkey PRIMARY KEY (granter_role_id, grantee_role_id, permission_id);
 \   ALTER TABLE ONLY public.role_grant_permissions DROP CONSTRAINT role_grant_permissions_pkey;
       public                 postgres    false    225    225    225            �           2606    16533 &   role_permissions role_permissions_pkey 
   CONSTRAINT     x   ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_pkey PRIMARY KEY (role_id, permission_id);
 P   ALTER TABLE ONLY public.role_permissions DROP CONSTRAINT role_permissions_pkey;
       public                 postgres    false    224    224            �           2606    16501    roles roles_pkey 
   CONSTRAINT     S   ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (role_id);
 :   ALTER TABLE ONLY public.roles DROP CONSTRAINT roles_pkey;
       public                 postgres    false    220            �           2606    16673    user_roles user_roles_pkey 
   CONSTRAINT     d   ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_pkey PRIMARY KEY (id_no, role_id);
 D   ALTER TABLE ONLY public.user_roles DROP CONSTRAINT user_roles_pkey;
       public                 postgres    false    223    223            �           2606    16405    users user_tb_email_unique 
   CONSTRAINT     V   ALTER TABLE ONLY public.users
    ADD CONSTRAINT user_tb_email_unique UNIQUE (email);
 D   ALTER TABLE ONLY public.users DROP CONSTRAINT user_tb_email_unique;
       public                 postgres    false    217            �           2606    16403    users user_tb_pkey 
   CONSTRAINT     S   ALTER TABLE ONLY public.users
    ADD CONSTRAINT user_tb_pkey PRIMARY KEY (id_no);
 <   ALTER TABLE ONLY public.users DROP CONSTRAINT user_tb_pkey;
       public                 postgres    false    217            �           1259    16695 %   fki_programs_college_id_colleges_fkey    INDEX     `   CREATE INDEX fki_programs_college_id_colleges_fkey ON public.programs USING btree (college_id);
 9   DROP INDEX public.fki_programs_college_id_colleges_fkey;
       public                 postgres    false    234            �           2606    16667 D   file_access_rule_conditions file_access_rule_conditions_rule_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.file_access_rule_conditions
    ADD CONSTRAINT file_access_rule_conditions_rule_id_fkey FOREIGN KEY (rule_id) REFERENCES public.file_access_rules(rule_id);
 n   ALTER TABLE ONLY public.file_access_rule_conditions DROP CONSTRAINT file_access_rule_conditions_rule_id_fkey;
       public               postgres    false    4830    233    232            �           2606    16656 3   file_access_rules file_access_rules_created_by_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.file_access_rules
    ADD CONSTRAINT file_access_rules_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id_no);
 ]   ALTER TABLE ONLY public.file_access_rules DROP CONSTRAINT file_access_rules_created_by_fkey;
       public               postgres    false    4808    217    232            �           2606    16646 0   file_access_rules file_access_rules_file_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.file_access_rules
    ADD CONSTRAINT file_access_rules_file_id_fkey FOREIGN KEY (file_id) REFERENCES public.files(file_id);
 Z   ALTER TABLE ONLY public.file_access_rules DROP CONSTRAINT file_access_rules_file_id_fkey;
       public               postgres    false    232    4822    227            �           2606    16651 6   file_access_rules file_access_rules_permission_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.file_access_rules
    ADD CONSTRAINT file_access_rules_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES public.permissions(permission_id);
 `   ALTER TABLE ONLY public.file_access_rules DROP CONSTRAINT file_access_rules_permission_id_fkey;
       public               postgres    false    232    222    4814            �           2606    16627 A   file_college_permissions file_college_permissions_college_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.file_college_permissions
    ADD CONSTRAINT file_college_permissions_college_id_fkey FOREIGN KEY (college_id) REFERENCES public.colleges(college_id);
 k   ALTER TABLE ONLY public.file_college_permissions DROP CONSTRAINT file_college_permissions_college_id_fkey;
       public               postgres    false    218    230    4810            �           2606    16622 >   file_college_permissions file_college_permissions_file_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.file_college_permissions
    ADD CONSTRAINT file_college_permissions_file_id_fkey FOREIGN KEY (file_id) REFERENCES public.files(file_id);
 h   ALTER TABLE ONLY public.file_college_permissions DROP CONSTRAINT file_college_permissions_file_id_fkey;
       public               postgres    false    4822    227    230            �           2606    16632 D   file_college_permissions file_college_permissions_permission_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.file_college_permissions
    ADD CONSTRAINT file_college_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES public.permissions(permission_id);
 n   ALTER TABLE ONLY public.file_college_permissions DROP CONSTRAINT file_college_permissions_permission_id_fkey;
       public               postgres    false    230    4814    222            �           2606    16602 8   file_role_permissions file_role_permissions_file_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.file_role_permissions
    ADD CONSTRAINT file_role_permissions_file_id_fkey FOREIGN KEY (file_id) REFERENCES public.files(file_id);
 b   ALTER TABLE ONLY public.file_role_permissions DROP CONSTRAINT file_role_permissions_file_id_fkey;
       public               postgres    false    4822    229    227            �           2606    16612 >   file_role_permissions file_role_permissions_permission_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.file_role_permissions
    ADD CONSTRAINT file_role_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES public.permissions(permission_id);
 h   ALTER TABLE ONLY public.file_role_permissions DROP CONSTRAINT file_role_permissions_permission_id_fkey;
       public               postgres    false    229    222    4814            �           2606    16607 8   file_role_permissions file_role_permissions_role_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.file_role_permissions
    ADD CONSTRAINT file_role_permissions_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(role_id);
 b   ALTER TABLE ONLY public.file_role_permissions DROP CONSTRAINT file_role_permissions_role_id_fkey;
       public               postgres    false    220    229    4812            �           2606    16582 8   file_user_permissions file_user_permissions_file_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.file_user_permissions
    ADD CONSTRAINT file_user_permissions_file_id_fkey FOREIGN KEY (file_id) REFERENCES public.files(file_id);
 b   ALTER TABLE ONLY public.file_user_permissions DROP CONSTRAINT file_user_permissions_file_id_fkey;
       public               postgres    false    227    228    4822            �           2606    16587 6   file_user_permissions file_user_permissions_id_no_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.file_user_permissions
    ADD CONSTRAINT file_user_permissions_id_no_fkey FOREIGN KEY (id_no) REFERENCES public.users(id_no);
 `   ALTER TABLE ONLY public.file_user_permissions DROP CONSTRAINT file_user_permissions_id_no_fkey;
       public               postgres    false    228    4808    217            �           2606    16592 >   file_user_permissions file_user_permissions_permission_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.file_user_permissions
    ADD CONSTRAINT file_user_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES public.permissions(permission_id);
 h   ALTER TABLE ONLY public.file_user_permissions DROP CONSTRAINT file_user_permissions_permission_id_fkey;
       public               postgres    false    4814    228    222            �           2606    16572    files files_uploaded_by_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.files
    ADD CONSTRAINT files_uploaded_by_fkey FOREIGN KEY (uploaded_by) REFERENCES public.users(id_no);
 F   ALTER TABLE ONLY public.files DROP CONSTRAINT files_uploaded_by_fkey;
       public               postgres    false    217    4808    227            �           2606    16690 *   programs programs_college_id_colleges_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.programs
    ADD CONSTRAINT programs_college_id_colleges_fkey FOREIGN KEY (college_id) REFERENCES public.colleges(college_id) NOT VALID;
 T   ALTER TABLE ONLY public.programs DROP CONSTRAINT programs_college_id_colleges_fkey;
       public               postgres    false    4810    218    234            �           2606    16554 B   role_grant_permissions role_grant_permissions_grantee_role_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.role_grant_permissions
    ADD CONSTRAINT role_grant_permissions_grantee_role_id_fkey FOREIGN KEY (grantee_role_id) REFERENCES public.roles(role_id);
 l   ALTER TABLE ONLY public.role_grant_permissions DROP CONSTRAINT role_grant_permissions_grantee_role_id_fkey;
       public               postgres    false    4812    220    225            �           2606    16549 B   role_grant_permissions role_grant_permissions_granter_role_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.role_grant_permissions
    ADD CONSTRAINT role_grant_permissions_granter_role_id_fkey FOREIGN KEY (granter_role_id) REFERENCES public.roles(role_id);
 l   ALTER TABLE ONLY public.role_grant_permissions DROP CONSTRAINT role_grant_permissions_granter_role_id_fkey;
       public               postgres    false    225    220    4812            �           2606    16559 @   role_grant_permissions role_grant_permissions_permission_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.role_grant_permissions
    ADD CONSTRAINT role_grant_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES public.permissions(permission_id);
 j   ALTER TABLE ONLY public.role_grant_permissions DROP CONSTRAINT role_grant_permissions_permission_id_fkey;
       public               postgres    false    222    4814    225            �           2606    16539 4   role_permissions role_permissions_permission_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES public.permissions(permission_id);
 ^   ALTER TABLE ONLY public.role_permissions DROP CONSTRAINT role_permissions_permission_id_fkey;
       public               postgres    false    224    4814    222            �           2606    16534 .   role_permissions role_permissions_role_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(role_id);
 X   ALTER TABLE ONLY public.role_permissions DROP CONSTRAINT role_permissions_role_id_fkey;
       public               postgres    false    224    4812    220            �           2606    16524 %   user_roles user_roles_college_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_college_id_fkey FOREIGN KEY (college_id) REFERENCES public.colleges(college_id);
 O   ALTER TABLE ONLY public.user_roles DROP CONSTRAINT user_roles_college_id_fkey;
       public               postgres    false    223    4810    218            �           2606    16514     user_roles user_roles_id_no_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_id_no_fkey FOREIGN KEY (id_no) REFERENCES public.users(id_no);
 J   ALTER TABLE ONLY public.user_roles DROP CONSTRAINT user_roles_id_no_fkey;
       public               postgres    false    4808    217    223            �           2606    16519 "   user_roles user_roles_role_id_fkey    FK CONSTRAINT     �   ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(role_id);
 L   ALTER TABLE ONLY public.user_roles DROP CONSTRAINT user_roles_role_id_fkey;
       public               postgres    false    4812    223    220            �   ^   x�Mʱ�0�O�	�!2aZO�J��AT\}C'�<��y�hI(j��8��P�ꭀd�?�o}�ׯ���d���|��Qx�)*�==Gk'      �      x������ � �      �      x������ � �      �      x������ � �      �      x������ � �      �      x������ � �      �      x������ � �      �   �   x�e���0E��ǘ�!{�[7j\�A��I��G�o��N��9���ڏ�Pv��o��s!1߬�ڶI��|�O����S��h�ə�읉���rh���C� ����\�`�w�4�wx&1�����k�����cק����H> 
��TCL\�v�9W=��j�W$�Ch�M$%�)ec���4�0j4-N�/h.!FC�T �Fx�q�����Q      �   *   x�3���K�/�M,���SIM�����O��tv����� ��
�      �      x������ � �      �   �   x�%ͱm���K��[��_�=rB��~��y?���F7��E]t�3�c;�c;�c;�c;�c;�󿭮��[��V��ӝ�t�'�p�7�p�7�p�7�p�7�p�7�p���r�-��"�,��"�,��"�,��"�,��"�\�"��E.r��~ݯ�u�����_��~ݯ�u���y9/�弜�3;�3;����<�L�      �   O   x��1
�0�z�1B�^�[�OMa"����㰾���w.p��ȩ���JAψ�l�LX��Z5$z��:�P�      �   B   x�3202�50�5 CNC�?.#$!#N#t!cNct!NN�`g13N3NgWG1SNS�X� O��      �   "  x���Ks�@�u�+f�.���Uc�Dy�C�l�l��>�fj\�wu�_�s�	'�Mط��X�ǐ�%X��T��c����Ϋ���Z�l�Q;��@��K�-X�)� ��k���V�ٳ^���:Jg��%rQ���y�m�W������������%sVm����fmF�?8,	�X�|�>i
t�����Vt�'"r�I�d��{�-��^���d�{(�N����
{���n����B�`����id��4��0�ʖ4�Ƙ�����
��2��v)H�1s�:�9���Mzm��,h��N�}zT鈚}����)���Mz��[+��_NI)�>�<N���������yQ���ji_J:��|Rޖ��%{i���4��;YP.�YW>�`ҔD(@�����O��=9�Z���=5���e1�R��lU2?�v�ͩ(�zs1|?�E�9m,�&y������+)�hy�u]>A�^s3;3��>�mX���hɜ����e,��^ԈK}-KY�D�:Q/��R����8��F��)      