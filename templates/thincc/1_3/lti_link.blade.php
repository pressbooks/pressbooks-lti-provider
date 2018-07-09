<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<cartridge_basiclti_link
        xmlns="http://www.imsglobal.org/xsd/imslticc_v1p3"
        xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0"
        xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0"
        xmlns:lticp="http://www.imsglobal.org/xsd/imslticp_v1p0"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p3 http://www.imsglobal.org/xsd/lti/ltiv1p3/imslticc_v1p3.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd">
    <blti:title>{{ $title }}</blti:title>
    <blti:secure_launch_url>{{ $url }}</blti:secure_launch_url>
    <blti:secure_icon>{{ $icon }}</blti:secure_icon>
    <blti:vendor>
        <lticp:code>pressbooks.education</lticp:code>
        <lticp:name>Pressbooks</lticp:name>
    </blti:vendor>
</cartridge_basiclti_link>