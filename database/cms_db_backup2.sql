PGDMP                      }            cms_db    17.5    17.5     W           0    0    ENCODING    ENCODING        SET client_encoding = 'UTF8';
                           false            X           0    0 
   STDSTRINGS 
   STDSTRINGS     (   SET standard_conforming_strings = 'on';
                           false            Y           0    0 
   SEARCHPATH 
   SEARCHPATH     8   SELECT pg_catalog.set_config('search_path', '', false);
                           false            Z           1262    16388    cms_db    DATABASE     �   CREATE DATABASE cms_db WITH TEMPLATE = template0 ENCODING = 'UTF8' LOCALE_PROVIDER = libc LOCALE = 'English_United States.1252';
    DROP DATABASE cms_db;
                     postgres    false            �            1259    18004    users    TABLE     �   CREATE TABLE public.users (
    id_no character(13) NOT NULL,
    fname character varying(100) NOT NULL,
    mname character varying(100),
    lname character varying(100) NOT NULL,
    email character varying(254) NOT NULL,
    password text NOT NULL
);
    DROP TABLE public.users;
       public         heap r       postgres    false            T          0    18004    users 
   TABLE DATA           L   COPY public.users (id_no, fname, mname, lname, email, password) FROM stdin;
    public               postgres    false    225   *       �           2606    18010    users pk_users_id_no 
   CONSTRAINT     U   ALTER TABLE ONLY public.users
    ADD CONSTRAINT pk_users_id_no PRIMARY KEY (id_no);
 >   ALTER TABLE ONLY public.users DROP CONSTRAINT pk_users_id_no;
       public                 postgres    false    225            �           2606    18012    users uq_users_email 
   CONSTRAINT     P   ALTER TABLE ONLY public.users
    ADD CONSTRAINT uq_users_email UNIQUE (email);
 >   ALTER TABLE ONLY public.users DROP CONSTRAINT uq_users_email;
       public                 postgres    false    225            T   "  x���Ks�@��u�+f�.7.���j"�(�]�l�l.��~�d�Y'��|������=q,�r�Ɛ�9����n+L!*R�;�Z��KI�g���*�qI�1݌��b&�� =6��c5������Q;+�Α��0f ;�:�`\��R�⽒�����$�rZ���ts5����Oۜ���u
���C[P�C���L��k!��[�����96�k�13��:M��+ھ��zƶ�ٞ���&|�*��@L#�J��8���t�>���
�m2����T����\��Ŕ@�_I��^�Z��3���B>��.�?n��S��Mz��a���/��t�<^~���^mf����(f�a1��w5��5!�oM��9{h�a�m��hB��&]y牟�MI��
XA
� J`J��\�Nh�#ݪ�7�d���'ʮ���(Xa6��l�M�0��TqTyX��2�;L��6�u	v��+)�hy�u]�����fV�Ֆvx]q�^WМ9m���{R�t�{I'�X�kE�b'�4V;G�K#֯����h4�?�&�     