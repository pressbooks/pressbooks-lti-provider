<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<topic xmlns="https://www.imsglobal.org/xsd/imsccv1p3/imsdt_v1p3"
       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:schemaLocation="https://www.imsglobal.org/xsd/imsccv1p3/imsdt_v1p3/profile/cc/ccv1p3/ccv1p3_imsdt_v1p3.xsd" >
    <title>{{ $title }}</title>
    <text texttype="text/html">{{ $content }}</text>
    {{ $points_possible_html }}
</topic>