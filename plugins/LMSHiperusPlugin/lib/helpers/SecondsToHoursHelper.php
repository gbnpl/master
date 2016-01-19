<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2015 LMS Developers
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

/**
 * LMSHiperusPlugin
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class SecondsToHoursHelper {
	public static function SecondsToHours($seconds) {
		if ($seconds <= 0)
			return '00:00:00';
		$h = intval($seconds / pow(60, 2));
		$m = intval($seconds / 60) % 60;
		$s = $seconds % 60;
		return sprintf("%02d:%02d:%02d", $h, $m, $s);
	}
}

?>
