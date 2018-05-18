<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<assignment xmlns="http://www.imsglobal.org/xsd/imscc_extensions/assignment"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.imsglobal.org/xsd/imscc_extensions/assignment http://www.imsglobal.org/profile/cc/cc_extensions/cc_extresource_assignmentv1p0_v1p0.xsd http://www.imsglobal.org/xsd/imscc_extensions/assignment "
            identifier="{{ $identifier }}">
    <title>{{ $title }}</title>
    <text texttype="text/html">{{ $url }}</text>
    <gradable points_possible="{{ $points_possible }}">true</gradable>
    <submission_formats>
        <format type="external_tool"/>
    </submission_formats>
</assignment>