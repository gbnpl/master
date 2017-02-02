<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2017 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

$this->BeginTrans();

$this->Execute("
	CREATE SEQUENCE hv_pstnusage_id_seq;
	ALTER TABLE hv_pstnusage ADD COLUMN id integer DEFAULT nextval('hv_pstnusage_id_seq'::text) NOT NULL;
	CREATE INDEX hv_pstnusage_id_idx ON hv_pstnusage (id);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017011900', 'dbversion_LMSHiperusPlugin'));

$this->CommitTrans();

?>
