PGDMP      7                }            cms_db    17.5    17.5                0    0    ENCODING    ENCODING        SET client_encoding = 'UTF8';
                           false                       0    0 
   STDSTRINGS 
   STDSTRINGS     (   SET standard_conforming_strings = 'on';
                           false                       0    0 
   SEARCHPATH 
   SEARCHPATH     8   SELECT pg_catalog.set_config('search_path', '', false);
                           false                        1262    16388    cms_db    DATABASE     �   CREATE DATABASE cms_db WITH TEMPLATE = template0 ENCODING = 'UTF8' LOCALE_PROVIDER = libc LOCALE = 'English_United States.1252';
    DROP DATABASE cms_db;
                     postgres    false            �            1259    16389    users    TABLE     �   CREATE TABLE public.users (
    id_no character(13) NOT NULL,
    fname character varying(100) NOT NULL,
    mname character varying(100),
    lname character varying(100) NOT NULL,
    email character varying(254) NOT NULL,
    password text NOT NULL
);
    DROP TABLE public.users;
       public         heap r       postgres    false                      0    16389    users 
   TABLE DATA           L   COPY public.users (id_no, fname, mname, lname, email, password) FROM stdin;
    public               postgres    false    217   6       �           2606    16405    users user_tb_email_unique 
   CONSTRAINT     V   ALTER TABLE ONLY public.users
    ADD CONSTRAINT user_tb_email_unique UNIQUE (email);
 D   ALTER TABLE ONLY public.users DROP CONSTRAINT user_tb_email_unique;
       public                 postgres    false    217            �           2606    16403    users user_tb_pkey 
   CONSTRAINT     S   ALTER TABLE ONLY public.users
    ADD CONSTRAINT user_tb_pkey PRIMARY KEY (id_no);
 <   ALTER TABLE ONLY public.users DROP CONSTRAINT user_tb_pkey;
       public                 postgres    false    217                  x������ � �     