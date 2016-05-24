<?php
$SMIL = '<smil>'
	.'<head></head>'
		.'<body>'
			.'<switch>'
				.'<video src="'. str_replace('.smil', '_720p.mp4', $SMILpath ) .'" system-bitrate="3000000" name="HD" title="HD"/>'
				.'<video src="'. str_replace('.smil', '_mobile.mp4', $SMILpath ) .'" system-bitrate="890000" name="Mobil" title="Mobil"/>'
			.'</switch>'
		.'</body>'
	.'</smil>';