--
-- PostgreSQL database dump
--

\restrict QYFahhbyBlgr5OOKAw28Ya8zBKVaJr9m1kdAfv8PG9gt9wyzSnoXJSCwpzHVa1n

-- Dumped from database version 18.4 (Debian 18.4-1.pgdg12+1)
-- Dumped by pg_dump version 18.6 (Debian 18.6-1.pgdg13+2)

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

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

-- *not* creating schema, since initdb creates it


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: participants; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.participants (
    id integer NOT NULL,
    user_id character varying(2) NOT NULL,
    claimed smallint DEFAULT 0 NOT NULL,
    nickname character varying(40),
    pin_hash character varying(255),
    team1 integer,
    team2 integer,
    team3 integer,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    joined_at timestamp with time zone
);


--
-- Name: participants_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.participants_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: participants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.participants_id_seq OWNED BY public.participants.id;


--
-- Name: pick_segments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pick_segments (
    id integer NOT NULL,
    participant_id integer NOT NULL,
    slot smallint NOT NULL,
    team_id integer NOT NULL,
    start_at timestamp with time zone NOT NULL,
    end_at timestamp with time zone
);


--
-- Name: pick_segments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pick_segments_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pick_segments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pick_segments_id_seq OWNED BY public.pick_segments.id;


--
-- Name: wc_fixtures; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.wc_fixtures (
    id integer NOT NULL,
    home_id integer NOT NULL,
    away_id integer NOT NULL,
    home_goals integer,
    away_goals integer,
    status character varying(10) DEFAULT 'NS'::character varying NOT NULL,
    stage character varying(20),
    winner character(1),
    utc_date timestamp with time zone,
    grp character varying(20),
    duration character varying(20),
    pen_home integer,
    pen_away integer,
    ft_home integer,
    ft_away integer
);


--
-- Name: wc_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.wc_meta (
    key character varying(50) NOT NULL,
    value text NOT NULL
);


--
-- Name: wc_teams; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.wc_teams (
    id integer NOT NULL,
    as_id integer,
    name character varying(100) NOT NULL,
    logo text
);


--
-- Name: wc_teams_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.wc_teams_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: wc_teams_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.wc_teams_id_seq OWNED BY public.wc_teams.id;


--
-- Name: participants id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.participants ALTER COLUMN id SET DEFAULT nextval('public.participants_id_seq'::regclass);


--
-- Name: pick_segments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pick_segments ALTER COLUMN id SET DEFAULT nextval('public.pick_segments_id_seq'::regclass);


--
-- Name: wc_teams id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wc_teams ALTER COLUMN id SET DEFAULT nextval('public.wc_teams_id_seq'::regclass);


--
-- Name: participants participants_nickname_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.participants
    ADD CONSTRAINT participants_nickname_key UNIQUE (nickname);


--
-- Name: participants participants_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.participants
    ADD CONSTRAINT participants_pkey PRIMARY KEY (id);


--
-- Name: participants participants_user_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.participants
    ADD CONSTRAINT participants_user_id_key UNIQUE (user_id);


--
-- Name: pick_segments pick_segments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pick_segments
    ADD CONSTRAINT pick_segments_pkey PRIMARY KEY (id);


--
-- Name: wc_fixtures wc_fixtures_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wc_fixtures
    ADD CONSTRAINT wc_fixtures_pkey PRIMARY KEY (id);


--
-- Name: wc_meta wc_meta_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wc_meta
    ADD CONSTRAINT wc_meta_pkey PRIMARY KEY (key);


--
-- Name: wc_teams wc_teams_as_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wc_teams
    ADD CONSTRAINT wc_teams_as_id_key UNIQUE (as_id);


--
-- Name: wc_teams wc_teams_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wc_teams
    ADD CONSTRAINT wc_teams_name_key UNIQUE (name);


--
-- Name: wc_teams wc_teams_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wc_teams
    ADD CONSTRAINT wc_teams_pkey PRIMARY KEY (id);


--
-- Name: idx_pick_seg_pid; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pick_seg_pid ON public.pick_segments USING btree (participant_id);


--
-- PostgreSQL database dump complete
--

\unrestrict QYFahhbyBlgr5OOKAw28Ya8zBKVaJr9m1kdAfv8PG9gt9wyzSnoXJSCwpzHVa1n

