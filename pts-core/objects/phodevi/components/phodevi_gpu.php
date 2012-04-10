<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2012, Phoronix Media
	Copyright (C) 2008 - 2012, Michael Larabel
	phodevi_gpu.php: The PTS Device Interface object for the graphics processor

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class phodevi_gpu extends phodevi_device_interface
{
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case 'identifier':
				$property = new phodevi_device_property('gpu_string', phodevi::std_caching);
				break;
			case 'model':
				$property = new phodevi_device_property('gpu_model', phodevi::smart_caching);
				break;
			case 'frequency':
				$property = new phodevi_device_property('gpu_frequency_string', phodevi::std_caching);
				break;
			case 'stock-frequency':
				$property = new phodevi_device_property('gpu_stock_frequency', phodevi::std_caching);
				break;
			case 'memory-capacity':
				$property = new phodevi_device_property('gpu_memory_size', phodevi::smart_caching);
				break;
			case 'aa-level':
				$property = new phodevi_device_property('gpu_aa_level', phodevi::no_caching);
				break;
			case 'af-level':
				$property = new phodevi_device_property('gpu_af_level', phodevi::no_caching);
				break;
			case 'compute-cores':
				$property = new phodevi_device_property('gpu_compute_cores', phodevi::smart_caching);
				break;
			case 'available-modes':
				$property = new phodevi_device_property('gpu_available_modes', phodevi::smart_caching);
				break;
			case 'screen-resolution':
				$property = new phodevi_device_property('gpu_screen_resolution', phodevi::std_caching);
				break;
			case 'screen-resolution-string':
				$property = new phodevi_device_property('gpu_screen_resolution_string', phodevi::std_caching);
				break;
			case '2d-acceleration':
				$property = new phodevi_device_property('gpu_2d_acceleration', phodevi::std_caching);
				break;
		}

		return $property;
	}
	public static function gpu_2d_acceleration()
	{
		$xorg_log = isset(phodevi::$vfs->xorg_log) ? phodevi::$vfs->xorg_log : false;
		$accel_2d = false;

		if($xorg_log)
		{
			if(strpos($xorg_log, 'EXA(0)'))
			{
				$accel_2d = 'EXA';
			}
			else if(strpos($xorg_log, 'UXA(0)'))
			{
				$accel_2d = 'UXA';
			}
			else if(strpos($xorg_log, 'SNA initialized'))
			{
				$accel_2d = 'SNA';
			}
			else if(strpos($xorg_log, 'shadowfb'))
			{
				$accel_2d = 'ShadowFB';
			}
		}

		return $accel_2d;
	}
	public static function set_property($identifier, $args)
	{
		switch($identifier)
		{
			case 'screen-resolution':
				$property = self::gpu_set_resolution($args);
				break;
		}

		return $property;
	}
	public static function special_settings_string()
	{
		$special_string = null;
		$extra_gfx_settings = array();
		$aa_level = phodevi::read_property('gpu', 'aa-level');
		$af_level = phodevi::read_property('gpu', 'af-level');

		if($aa_level)
		{
			array_push($extra_gfx_settings, 'AA: ' . $aa_level);
		}
		if($af_level)
		{
			array_push($extra_gfx_settings, 'AF: ' . $af_level);
		}

		if(count($extra_gfx_settings) > 0)
		{
			$special_string = implode(' - ', $extra_gfx_settings);
		}

		return $special_string;
	}
	public static function gpu_set_resolution($args)
	{
		if(count($args) != 2 || phodevi::is_windows() || phodevi::is_macosx() || !pts_client::executable_in_path('xrandr'))
		{
			return false;
		}

		$width = $args[0];
		$height = $args[1];

		shell_exec('xrandr -s ' . $width . 'x' . $height . ' 2>&1');

		return phodevi::read_property('gpu', 'screen-resolution') == array($width, $height); // Check if video resolution set worked
	}
	public static function gpu_aa_level()
	{
		// Determine AA level if over-rode
		$aa_level = false;

		if(phodevi::is_nvidia_graphics())
		{
			$nvidia_fsaa = phodevi_parser::read_nvidia_extension('FSAA');

			switch($nvidia_fsaa)
			{
				case 1:
					$aa_level = '2x Bilinear';
					break;
				case 5:
					$aa_level = '4x Bilinear';
					break;
				case 7:
					$aa_level = '8x';
					break;
				case 8:
					$aa_level = '16x';
					break;
				case 10:
					$aa_level = '8xQ';
					break;
				case 12:
					$aa_level = '16xQ';
					break;
			}
		}
		else if(phodevi::is_ati_graphics() && phodevi::is_linux())
		{
			$ati_fsaa = phodevi_linux_parser::read_amd_pcsdb('OpenGL,AntiAliasSamples');
			$ati_fsaa_filter = phodevi_linux_parser::read_amd_pcsdb('OpenGL,AAF');

			if(!empty($ati_fsaa))
			{
				if($ati_fsaa_filter == '0x00000000')
				{
					// Filter: Box
					switch($ati_fsaa)
					{
						case '0x00000002':
							$aa_level = '2x Box';
							break;
						case '0x00000004':
							$aa_level = '4x Box';
							break;
						case '0x00000008':
							$aa_level = '8x Box';
							break;
					}
				}
				else if($ati_fsaa_filter == '0x00000001')
				{
					// Filter: Narrow-tent
					switch($ati_fsaa)
					{
						case '0x00000002':
							$aa_level = '4x Narrow-tent';
							break;
						case '0x00000004':
							$aa_level = '8x Narrow-tent';
							break;
						case '0x00000008':
							$aa_level = '12x Narrow-tent';
							break;
					}
				}
				else if($ati_fsaa_filter == '0x00000002')
				{
					// Filter: Wide-tent
					switch($ati_fsaa)
					{
						case '0x00000002':
							$aa_level = '6x Wide-tent';
							break;
						case '0x00000004':
							$aa_level = '8x Wide-tent';
							break;
						case '0x00000008':
							$aa_level = '16x Wide-tent';
							break;
					}

				}
				else if($ati_fsaa_filter == '0x00000003')
				{
					// Filter: Edge-detect
					switch($ati_fsaa)
					{
						case '0x00000004':
							$aa_level = '12x Edge-detect';
							break;
						case '0x00000008':
							$aa_level = '24x Edge-detect';
							break;
					}
				}
			}
		}

		return $aa_level;
	}
	public static function gpu_af_level()
	{
		// Determine AF level if over-rode
		$af_level = false;

		if(phodevi::is_nvidia_graphics())
		{
			$nvidia_af = phodevi_parser::read_nvidia_extension('LogAniso');

			switch($nvidia_af)
			{
				case 1:
					$af_level = '2x';
					break;
				case 2:
					$af_level = '4x';
					break;
				case 3:
					$af_level = '8x';
					break;
				case 4:
					$af_level = '16x';
					break;
			}
		}
		else if(phodevi::is_ati_graphics() && phodevi::is_linux())
		{
			$ati_af = phodevi_linux_parser::read_amd_pcsdb('OpenGL,AnisoDegree');

			if(!empty($ati_af))
			{
				switch($ati_af)
				{
					case '0x00000002':
						$af_level = '2x';
						break;
					case '0x00000004':
						$af_level = '4x';
						break;
					case '0x00000008':
						$af_level = '8x';
						break;
					case '0x00000010':
						$af_level = '16x';
						break;
				}
			}
		}

		return $af_level;
	}
	public static function gpu_compute_cores()
	{
		// Determine AF level if over-rode
		$cores = 0;

		if(phodevi::is_nvidia_graphics())
		{
			$cores = phodevi_parser::read_nvidia_extension('CUDACores');
		}

		return $cores;
	}
	public static function gpu_xrandr_resolution()
	{
		$resolution = false;

		if(pts_client::executable_in_path('xrandr') && getenv('DISPLAY'))
		{
			// Read resolution from xrandr
			$info = shell_exec('xrandr 2>&1 | grep "*"');

			if(strpos($info, '*') !== false)
			{
				$res = pts_strings::trim_explode('x', $info);
				$res[0] = substr($res[0], strrpos($res[0], ' '));
				$res[1] = substr($res[1], 0, strpos($res[1], ' '));
				$res = array_map('trim', $res);

				if(is_numeric($res[0]) && is_numeric($res[1]))
				{
					$resolution = array($res[0], $res[1]);
				}
			}
		}

		return $resolution;
	}
	public static function gpu_screen_resolution()
	{
		$resolution = false;

		if((($default_mode = getenv('DEFAULT_VIDEO_MODE')) != false))
		{
			$default_mode = explode('x', $default_mode);

			if(count($default_mode) == 2 && is_numeric($default_mode[0]) && is_numeric($default_mode[1]))
			{
				return $default_mode;
			}
		}

		if(phodevi::is_macosx())
		{
			$info = pts_strings::trim_explode(' ', phodevi_osx_parser::read_osx_system_profiler('SPDisplaysDataType', 'Resolution'));
			$resolution = array();
			$resolution[0] = $info[0];
			$resolution[1] = $info[2];
		}
		else if(phodevi::is_linux() || phodevi::is_bsd() || phodevi::is_solaris())
		{
			if(phodevi::is_linux())
			{
				// Before calling xrandr first try to get the resolution through KMS path
				foreach(pts_file_io::glob('/sys/class/drm/card*/*/modes') as $connector_path)
				{
					$connector_path = dirname($connector_path) . '/';

					if(is_file($connector_path . 'enabled') && pts_file_io::file_get_contents($connector_path . 'enabled') == 'enabled')
					{
						$mode = pts_arrays::first_element(explode("\n", pts_file_io::file_get_contents($connector_path . 'modes')));
						$info = pts_strings::trim_explode('x', $mode);

						if(count($info) == 2)
						{
							$resolution = $info;
							break;
						}
					}
				}
			}

			if($resolution == false && pts_client::executable_in_path('xrandr'))
			{
				$resolution = self::gpu_xrandr_resolution();
			}

			if($resolution == false && phodevi::is_nvidia_graphics())
			{
				// Way to find resolution through NVIDIA's NV-CONTROL extension
				// But rely upon xrandr first since when using NVIDIA TwinView the reported FrontEndResolution may be the smaller of the two
				if(($frontend_res = phodevi_parser::read_nvidia_extension('FrontendResolution')) != false)
				{
					$resolution = pts_strings::comma_explode($frontend_res);
				}
			}

			if($resolution == false)
			{
				// Fallback to reading resolution from xdpyinfo
				foreach(phodevi_parser::read_xdpy_monitor_info() as $monitor_line)
				{
					$this_resolution = substr($monitor_line, strpos($monitor_line, ': ') + 2);
					$this_resolution = substr($this_resolution, 0, strpos($this_resolution, ' '));
					$this_resolution = explode('x', $this_resolution);

					if(count($this_resolution) == 2 && is_numeric($this_resolution[0]) && is_numeric($this_resolution[1]))
					{
						$resolution = $this_resolution;
						break;
					}
				}
			}

			if($resolution == false && is_readable('/sys/class/graphics/fb0/virtual_size'))
			{
				// As last fall-back try reading size of fb
				$virtual_size = explode(',', pts_file_io::file_get_contents('/sys/class/graphics/fb0/virtual_size'));

				if(count($virtual_size) == 2 && is_numeric($virtual_size[0]) && is_numeric($virtual_size[1]))
				{
					$resolution = $virtual_size;
				}
			}
		}

		return $resolution == false ? array(-1, -1) : $resolution;
	}
	public static function gpu_screen_resolution_string()
	{
		// Return the current screen resolution
		$resolution = implode('x', phodevi::read_property('gpu', 'screen-resolution'));

		if($resolution == '-1x-1')
		{
			$resolution = null;
		}

		return $resolution;
	}
	public static function gpu_available_modes()
	{
		// XRandR available modes
		$current_resolution = phodevi::read_property('gpu', 'screen-resolution');
		$current_pixel_count = $current_resolution[0] * $current_resolution[1];
		$available_modes = array();
		$supported_ratios = array(1.60, 1.25, 1.33, 1.70, 1.71, 1.78);
		$ignore_modes = array(
			array(640, 400),
			array(720, 480), array(832, 624),
			array(960, 540), array(960, 600),
			array(896, 672), array(928, 696),
			array(960, 720), array(1152, 864),
			array(1280, 720), array(1360, 768),
			array(1776, 1000), array(1792, 1344),
			array(1800, 1440), array(1856, 1392),
			array(2048, 1536)
			);

		if($override_check = (($override_modes = getenv('OVERRIDE_VIDEO_MODES')) != false))
		{
			$override_modes = pts_strings::comma_explode($override_modes);

			for($i = 0; $i < count($override_modes); $i++)
			{
				$override_modes[$i] = explode('x', $override_modes[$i]);
			}
		}

		// Attempt reading available modes from xrandr
		if(pts_client::executable_in_path('xrandr') && !phodevi::is_macosx()) // MacOSX has xrandr but currently on at least my setup will emit a Bus Error when called
		{
			$xrandr_lines = array_reverse(explode("\n", shell_exec('xrandr 2>&1')));

			foreach($xrandr_lines as $xrandr_mode)
			{
				if(($cut_point = strpos($xrandr_mode, '(')) > 0)
				{
					$xrandr_mode = substr($xrandr_mode, 0, $cut_point);
				}

				$res = pts_strings::trim_explode('x', $xrandr_mode);

				if(count($res) == 2)
				{
					$res[0] = substr($res[0], strrpos($res[0], ' '));
					$res[1] = substr($res[1], 0, strpos($res[1], ' '));

					if(is_numeric($res[0]) && is_numeric($res[1]))
					{
						$m = array($res[0], $res[1]);
						if(!in_array($m, $available_modes))
						{
							// Don't repeat modes
							array_push($available_modes, $m);
						}
					}
				}
			}
		}

		if(count($available_modes) <= 2)
		{
			// Fallback to providing stock modes
			$stock_modes = array(
				array(800, 600), array(1024, 768),
				array(1280, 1024), array(1400, 1050), 
				array(1680, 1050), array(1600, 1200),
				array(1920, 1080), array(2560, 1600));
			$available_modes = array();

			for($i = 0; $i < count($stock_modes); $i++)
			{
				if($stock_modes[$i][0] <= $current_resolution[0] && $stock_modes[$i][1] <= $current_resolution[1])
				{
					array_push($available_modes, $stock_modes[$i]);
				}
			}
		}

		foreach($available_modes as $mode_index => $mode)
		{
			$this_ratio = pts_math::set_precision($mode[0] / $mode[1], 2);

			if($override_check && !in_array($mode, $override_modes))
			{
				// Using override modes and this mode is not present
				unset($available_modes[$mode_index]);
			}
			else if($current_pixel_count > 614400 && ($mode[0] * $mode[1]) < 480000 && stripos(phodevi::read_name('gpu'), 'llvmpipe') === false)
			{
				// For displays larger than 1024 x 600, drop modes below 800 x 600 unless llvmpipe is being used
				unset($available_modes[$mode_index]);
			}
			else if($current_pixel_count > 480000 && !in_array($this_ratio, $supported_ratios))
			{
				// For displays larger than 800 x 600, ensure reading from a supported ratio
				unset($available_modes[$mode_index]);
			}
			else if(in_array($mode, $ignore_modes))
			{
				// Mode is to be ignored
				unset($available_modes[$mode_index]);
			}
		}

		// Sort available modes in order
		$unsorted_modes = $available_modes;
		$available_modes = array();
		$mode_pixel_counts = array();

		foreach($unsorted_modes as $this_mode)
		{
			if(count($this_mode) == 2)
			{
				array_push($mode_pixel_counts, $this_mode[0] * $this_mode[1]);
			}
		}

		// Sort resolutions by true pixel count resolution
		sort($mode_pixel_counts);
		foreach($mode_pixel_counts as &$mode_pixel_count)
		{
			foreach($unsorted_modes as $mode_index => $mode)
			{
				if($mode[0] * $mode[1] == $mode_pixel_count)
				{
					array_push($available_modes, $mode);
					unset($unsorted_modes[$mode_index]);
					break;
				}
			}
		}

		if(count($available_modes) == 0 && $override_check)
		{
			// Write in the non-standard modes that were overrode
			foreach($override_modes as $mode)
			{
				if(is_array($mode) && count($mode) == 2)
				{
					array_push($available_modes, $mode);
				}
			}
		}

		return $available_modes;
	}
	public static function gpu_memory_size()
	{
		// Graphics memory capacity
		$video_ram = -1;

		if(($vram = getenv('VIDEO_MEMORY')) != false && is_numeric($vram))
		{
			$video_ram = $vram;
		}
		else if(is_file('/sys/kernel/debug/dri/0/memory'))
		{
			// This is how some of the Nouveau DRM VRAM is reported
			$memory = file_get_contents('/sys/kernel/debug/dri/0/memory');

			if(($x = strpos($memory, 'VRAM total: ')) !== false)
			{
				$memory = substr($memory, ($x + 12));

				if(($x = strpos($memory, 'KiB')) !== false)
				{
					$memory = substr($memory, 0, $x);

					if(is_numeric($memory))
					{
						$video_ram = $memory / 1024;
					}
				}
			}
		}
		else if(phodevi::is_nvidia_graphics() && ($NVIDIA = phodevi_parser::read_nvidia_extension('VideoRam')) > 0) // NVIDIA blob
		{
			$video_ram = $NVIDIA / 1024;
		}
		else if(phodevi::is_macosx())
		{
			$info = phodevi_osx_parser::read_osx_system_profiler('SPDisplaysDataType', 'VRAM');
			$info = explode(' ', $info);
			$video_ram = $info[0];

			if($info[1] == 'GB')
			{
				$video_ram *= 1024;
			}
		}

		if($video_ram == -1 && isset(phodevi::$vfs->xorg_log))
		{
			// Attempt Video RAM detection using X log
			// fglrx driver reports video memory to: (--) fglrx(0): VideoRAM: XXXXXX kByte, Type: DDR
			// xf86-video-ati, xf86-video-intel, and xf86-video-radeonhd also report their memory information in a similar format
			$info = phodevi::$vfs->xorg_log;

			if(($pos = stripos($info, 'RAM:') + 5) > 5 || ($pos = strpos($info, 'RAM=') + 4) > 4)
			{
				$info = substr($info, $pos);
				$info = substr($info, 0, strpos($info, ' '));

				if(!is_numeric($info) && ($cut = strpos($info, ',')))
				{
					$info = substr($info, 0, $cut);
				}

				if(is_numeric($info) && $info > 65535)
				{
					$video_ram = intval($info) / 1024;
				}
			}
		}
		if($video_ram == -1 && pts_client::executable_in_path('dmesg'))
		{
			// Fallback to try to find vRAM from dmesg
			$info = shell_exec('dmesg 2> /dev/null');

			if(($x = strpos($info, 'Detected VRAM RAM=')) !== false)
			{
				// Radeon DRM at least reports: [drm] Detected VRAM RAM=2048M, BAR=256M
				$info = substr($info, $x + 18);
				$info = substr($info, 0, strpos($info, 'M'));
			}
			else if(($x = strpos($info, 'M of VRAM')) !== false)
			{
				// Radeon DRM around Linux ~3.0 reports e.g.: [drm] radeon: 2048M of VRAM memory ready
				$info = substr($info, 0, $x);
				$info = substr($info, strrpos($info, ' ') + 1);
			}
			else if(($x = strpos($info, 'MiB VRAM')) !== false)
			{
				// Nouveau DRM around Linux ~3.0 reports e.g.: [drm] nouveau XXX: Detected 1024MiB VRAM
				$info = substr($info, 0, $x);
				$info = substr($info, strrpos($info, ' ') + 1);
			}

			if(is_numeric($info))
			{
				$video_ram = $info;
			}
		}

		if($video_ram == -1 || !is_numeric($video_ram) || $video_ram < 64)
		{
			$video_ram = 64; // default to 64MB of video RAM as something sane...
		}

		return $video_ram;
	}
	public static function gpu_string()
	{
		$info = phodevi_parser::read_glx_renderer();

		if(stripos($info, 'llvmpipe'))
		{
			return 'LLVMpipe';
		}
		else
		{
			return phodevi::read_property('gpu', 'model') . ' ' . phodevi::read_property('gpu', 'frequency');
		}
	}
	public static function gpu_frequency_string()
	{
		$freq = phodevi::read_property('gpu', 'stock-frequency');
		$freq_string = null;

		if($freq[0] != 0)
		{
			$freq_string = $freq[0];

			if($freq[1] != 0)
			{
				$freq_string .= '/' . $freq[1];
			}

			$freq_string .= 'MHz';
		}

		return ($freq_string != null ? ' (' . $freq_string . ')' : null);
	}
	public static function gpu_stock_frequency()
	{
		// Graphics processor stock frequency
		$core_freq = 0;
		$mem_freq = 0;

		if(phodevi::is_nvidia_graphics() && phodevi::is_macosx() == false) // NVIDIA GPU
		{
			// GPUDefault3DClockFreqs is the default and does not show under/over-clocking
			$clock_freqs_3d = pts_strings::comma_explode(phodevi_parser::read_nvidia_extension('GPU3DClockFreqs'));

			if(is_array($clock_freqs_3d) && isset($clock_freqs_3d[1]))
			{
				list($core_freq, $mem_freq) = $clock_freqs_3d;
			}
		}
		else if(phodevi::is_ati_graphics() && phodevi::is_linux()) // ATI GPU
		{
			$od_clocks = phodevi_linux_parser::read_ati_overdrive('CurrentPeak');

			if(is_array($od_clocks) && count($od_clocks) >= 2) // ATI OverDrive
			{
				list($core_freq, $mem_freq) = $od_clocks;
			}
		}
		else if(phodevi::is_mesa_graphics())
		{
			switch(phodevi::read_property('system', 'display-driver'))
			{
				case 'nouveau':
					if(is_file('/sys/class/drm/card0/device/performance_level'))
					{
						/*
							EXAMPLE OUTPUTS:
							memory 1000MHz core 500MHz voltage 1300mV fanspeed 100%
							3: memory 333MHz core 500MHz shader 1250MHz fanspeed 100%
							c: memory 333MHz core 500MHz shader 1250MHz
						*/

						$performance_level = pts_file_io::file_get_contents('/sys/class/drm/card0/device/performance_level');
						$performance_level = explode(' ', $performance_level);

						$core_string = array_search('core', $performance_level);
						if($core_string !== false && isset($performance_level[($core_string + 1)]))
						{
							$core_string = str_ireplace('MHz', null, $performance_level[($core_string + 1)]);
							if(is_numeric($core_string))
							{
								$core_freq = $core_string;
							}
						}

						$mem_string = array_search('memory', $performance_level);
						if($mem_string !== false && isset($performance_level[($mem_string + 1)]))
						{
							$mem_string = str_ireplace('MHz', null, $performance_level[($mem_string + 1)]);
							if(is_numeric($mem_string))
							{
								$mem_freq = $mem_string;
							}
						}
					}
					break;
				case 'radeon':
					if(is_file('/sys/kernel/debug/dri/0/radeon_pm_info'))
					{
						// radeon_pm_info should be present with Linux 2.6.34+
						foreach(pts_strings::trim_explode("\n", pts_file_io::file_get_contents('/sys/kernel/debug/dri/0/radeon_pm_info')) as $pm_line)
						{
							list($descriptor, $value) = pts_strings::colon_explode($pm_line);

							switch($descriptor)
							{
								case 'default engine clock':
									$core_freq = pts_arrays::first_element(explode(' ', $value)) / 1000;
									break;
								case 'default memory clock':
									$mem_freq = pts_arrays::first_element(explode(' ', $value)) / 1000;
									break;
							}
						}
					}
					else
					{
						// Old ugly way of handling the clock information
						$log_parse = isset(phodevi::$vfs->xorg_log) ? phodevi::$vfs->xorg_log : null;

						$core_freq = substr($log_parse, strpos($log_parse, 'Default Engine Clock: ') + 22);
						$core_freq = substr($core_freq, 0, strpos($core_freq, "\n"));
						$core_freq = is_numeric($core_freq) ? $core_freq / 1000 : 0;

						$mem_freq = substr($log_parse, strpos($log_parse, 'Default Memory Clock: ') + 22);
						$mem_freq = substr($mem_freq, 0, strpos($mem_freq, "\n"));
						$mem_freq = is_numeric($mem_freq) ? $mem_freq / 1000 : 0;
					}				
					break;
				case 'intel':
					// try to read the maximum dynamic frequency
					if(is_file('/sys/kernel/debug/dri/0/i915_max_freq'))
					{
						$i915_max_freq = pts_file_io::file_get_contents('/sys/kernel/debug/dri/0/i915_max_freq');
						$freq_mhz = substr($i915_max_freq, strpos($i915_max_freq, ': ') + 2);

						if(is_numeric($freq_mhz))
						{
							$core_freq = $freq_mhz;
						}
					}

					// Fallback to base frequency
					if($core_freq == 0 && is_file('/sys/kernel/debug/dri/0/i915_cur_delayinfo'))
					{
						$i915_cur_delayinfo = file_get_contents('/sys/kernel/debug/dri/0/i915_cur_delayinfo');
						$freq = strpos($i915_cur_delayinfo, 'Max non-overclocked (RP0) frequency: ');

						if($freq === false)
						{
							$freq = strpos($i915_cur_delayinfo, 'Nominal (RP1) frequency: ');
						}

						if($freq !== false)
						{
							$freq_mhz = substr($i915_cur_delayinfo, strpos($i915_cur_delayinfo, ': ', $freq) + 2);
							$freq_mhz = trim(substr($freq_mhz, 0, strpos($freq_mhz, 'MHz')));

							if(is_numeric($freq_mhz))
							{
								$core_freq = $freq_mhz;
							}
						}
					}
					break;
			}
		}

		if(!is_numeric($core_freq))
		{
			$core_freq = 0;
		}
		if(!is_numeric($mem_freq))
		{
			$mem_freq = 0;
		}

		return array($core_freq, $mem_freq);
	}
	public static function gpu_model()
	{
		// Report graphics processor string
		$info = phodevi_parser::read_glx_renderer();
		$video_ram = phodevi::read_property('gpu', 'memory-capacity');

		if(phodevi::is_ati_graphics() && phodevi::is_linux())
		{
			$crossfire_status = phodevi_linux_parser::read_amd_pcsdb('SYSTEM/Crossfire/chain/*,Enable');
			$crossfire_status = pts_arrays::to_array($crossfire_status);
			$crossfire_card_count = 0;

			for($i = 0; $i < count($crossfire_status); $i++)
			{
				if($crossfire_status[$i] == '0x00000001')
				{
					$crossfire_card_count += 2; // For now assume each chain is 2 cards, but proper way would be NumSlaves + 1
				}
			}			

			$adapters = phodevi_linux_parser::read_amd_graphics_adapters();

			if(count($adapters) > 0)
			{
				$video_ram = $video_ram > 64 ? ' ' . $video_ram . 'MB' : null; // assume more than 64MB of vRAM

				if($crossfire_card_count > 1 && $crossfire_card_count <= count($adapters))
				{
					$unique_adapters = array_unique($adapters);

					if(count($unique_adapters) == 1)
					{
						if(strpos($adapters[0], 'X2') > 0 && $crossfire_card_count > 1)
						{
							$crossfire_card_count -= 1;
						}

						$info = $crossfire_card_count . ' x ' . $adapters[0] . $video_ram . ' CrossFire';
					}
					else
					{
						$info = implode(', ', $unique_adapters) . ' CrossFire';
					}
				}
				else
				{
					$info = $adapters[0] . $video_ram;
				}
			}
		}
		else if(phodevi::is_nvidia_graphics())
		{
			if($info == null)
			{
				if(pts_client::executable_in_path('nvidia-settings'))
				{
					$nv_gpus = shell_exec('nvidia-settings -q gpus 2>&1');

					// TODO: search for more than one GPU
					$nv_gpus = substr($nv_gpus, strpos($nv_gpus, '[0]'));
					$nv_gpus = substr($nv_gpus, strpos($nv_gpus, '(') + 1);
					$nv_gpus = substr($nv_gpus, 0, strpos($nv_gpus, ')'));

					if(stripos($nv_gpus, 'GeForce') !== false || stripos($nv_gpus, 'Quadro') !== false)
					{
						$info = $nv_gpus;
					}
				}
			}

			$sli_mode = phodevi_parser::read_nvidia_extension('SLIMode');

			if(!empty($sli_mode) && $sli_mode != 'Off')
			{
				$info .= ' SLI';
			}
		}

		if(phodevi::is_solaris())
		{
			if(($cut = strpos($info, 'DRI ')) !== false)
			{
				$info = substr($info, ($cut + 4));
			}
			if(($cut = strpos($info, ' Chipset')) !== false)
			{
				$info = substr($info, 0, $cut);
			}
		}
		else if(phodevi::is_bsd())
		{
			$drm_info = phodevi_bsd_parser::read_sysctl('dev.drm.0.%desc');

			if(!$drm_info)
			{
				$drm_info = phodevi_bsd_parser::read_sysctl('dev.nvidia.0.%desc');
			}

			if(!$drm_info)
			{
				$agp_info = phodevi_bsd_parser::read_sysctl('dev.agp.0.%desc');

				if($agp_info != false)
				{
					$info = $agp_info;
				}
			}
			else
			{
				$info = $drm_info;
			}

			if($info == null && isset(phodevi::$vfs->xorg_log))
			{
				$xorg_log = phodevi::$vfs->xorg_log;
				if(($e = strpos($xorg_log, ' at 01@00:00:0')) !== false)
				{
					$xorg_log = substr($xorg_log, 0, $e);
					$info = substr($xorg_log, strrpos($xorg_log, 'Found ') + 6);
				}
			}
		}
		else if(phodevi::is_windows())
		{
			$info = phodevi_windows_parser::read_cpuz('Display Adapters', 'Name');
		}
	
		if(empty($info) || strpos($info, 'Mesa ') !== false || strpos($info, 'Gallium ') !== false)
		{
			if(phodevi::is_macosx())
			{
				$info = phodevi_osx_parser::read_osx_system_profiler('SPDisplaysDataType', 'ChipsetModel');
			}
			if(phodevi::is_windows() == false)
			{
				$info_pci = phodevi_linux_parser::read_pci('VGA compatible controller', false);

				if(!empty($info_pci))
				{
					$info = $info_pci;

					if(strpos($info, 'Intel 2nd Generation Core Family') !== false)
					{
						// Try to come up with a better non-generic string
						$was_reset = false;
						if(isset(phodevi::$vfs->xorg_log))
						{
							/*
							$ cat /var/log/Xorg.0.log | grep -i Chipset
							[     8.421] (II) intel: Driver for Intel Integrated Graphics Chipsets: i810,
							[     8.421] (II) VESA: driver for VESA chipsets: vesa
							[     8.423] (II) intel(0): Integrated Graphics Chipset: Intel(R) Sandybridge Mobile (GT2+)
							[     8.423] (--) intel(0): Chipset: "Sandybridge Mobile (GT2+)"
							*/

							$xorg_log = phodevi::$vfs->xorg_log;
							if(($x = strpos($xorg_log, '(0): Chipset: ')) !== false)
							{
								$xorg_log = substr($xorg_log, ($x + 19));
								$xorg_log = str_replace(array('(R)', '"'), null, substr($xorg_log, 0, strpos($xorg_log, PHP_EOL)));

								if(stripos($xorg_log, 'Intel') === false)
								{
									$xorg_log = 'Intel ' . $xorg_log;
								}

								// if string is too long, likely not product
								if(!isset($xorg_log[45]))
								{
									$info = $xorg_log;
									$was_reset = true;
								}
							}
						}
						if($was_reset == false && is_readable('/sys/kernel/debug/dri/0/i915_capabilities'))
						{
							$i915_caps = file_get_contents('/sys/kernel/debug/dri/0/i915_capabilities');
							if(($x = strpos($i915_caps, 'gen: ')) !== false)
							{
								$gen = substr($i915_caps, ($x + 5));
								$gen = substr($gen, 0, strpos($gen, PHP_EOL));

								if(is_numeric($gen))
								{
									$info = 'Intel Gen' . $gen;

									if(strpos($i915_caps, 'is_mobile: yes') !== false)
									{
										$info .= ' Mobile';
									}
								}
							}
						}
					}
				}
			}

			if(($start_pos = strpos($info, ' DRI ')) > 0)
			{
				$info = substr($info, $start_pos + 5);
			}

			if(empty($info) && isset(phodevi::$vfs->xorg_log))
			{
				$log_parse = phodevi::$vfs->xorg_log;
				$log_parse = substr($log_parse, strpos($log_parse, 'Chipset') + 8);
				$log_parse = substr($log_parse, 0, strpos($log_parse, 'found'));

				if(strpos($log_parse, '(--)') === false && strlen(str_ireplace(array('ATI', 'NVIDIA', 'VIA', 'Intel'), '', $log_parse)) != strlen($log_parse))
				{
					$info = $log_parse;
				}
			}

			if(empty($info) && is_readable('/sys/class/graphics/fb0/name'))
			{
				switch(pts_file_io::file_get_contents('/sys/class/graphics/fb0/name'))
				{
					case 'omapdrm':
						$info = 'Texas Instruments OMAP'; // The OMAP DRM driver currently is for OMAP2/3/4 hardware
						break;
					case 'exynos':
						$info = 'Samsung EXYNOS'; // The Exynos DRM driver
						break;
					case 'tegra_fb':
						$info = 'NVIDIA TEGRA'; // The Exynos DRM driver
						break;
					default:
						if(is_file('/dev/mali'))
						{
							$info = 'ARM Mali'; // One of the ARM Mali models
						}
						break;
				}

			}

			if(substr($info, -1) == ')' && ($open_p = strrpos($info, '(')) != false)
			{
				$end_check = strpos($info, ' ', $open_p);
				$to_check = substr($info, ($open_p + 1), ($end_check - $open_p - 1));

				// Don't report card revision from PCI info
				if($to_check == 'rev')
				{
					$info = substr($info, 0, $open_p - 1);
				}
			}
		}

		if(($bracket_open = strpos($info, '[')) !== false)
		{
			// Report only the information inside the brackets if it's more relevant...
			// Mainly with Linux systems where the PCI information is reported like 'nVidia GF104 [GeForce GTX 460]'

			if(($bracket_close = strpos($info, ']', ($bracket_open + 1))) !== false)
			{
				$inside_bracket = substr($info, ($bracket_open + 1), ($bracket_close - $bracket_open - 1));

				if(stripos($inside_bracket, 'Quadro') !== false || stripos($inside_bracket, 'GeForce') !== false)
				{
					$info = $inside_bracket . ' ' . substr($info, ($bracket_close + 1));
				}
				else if(stripos($inside_bracket, 'Radeon') !== false || stripos($inside_bracket, 'Fire') !== false || stripos($inside_bracket, 'Fusion') !== false)
				{
					$info = $inside_bracket . ' ' . substr($info, ($bracket_close + 1));
				}
			}
		}

		if(stripos($info, 'NVIDIA') === false && (stripos($info, 'Quadro') !== false || stripos($info, 'GeForce') !== false))
		{
			$info = 'NVIDIA' . ' ' . $info;
		}
		else if((stripos($info, 'ATI') === false && stripos($info, 'AMD') === false) && (stripos($info, 'Radeon') !== false || stripos($info, 'Fire') !== false || stripos($info, 'Fusion') !== false))
		{
			// Fire would be for FireGL or FirePro hardware
			$info = 'AMD ' . $info;
		}

		if($video_ram > 64 && strpos($info, $video_ram) == false) // assume more than 64MB of vRAM
		{
			$info .= ' ' . $video_ram . 'MB';
		}
	
		$clean_phrases = array('OpenGL Engine');
		$info = str_replace($clean_phrases, null, $info);

		return $info;
	}
}

?>
