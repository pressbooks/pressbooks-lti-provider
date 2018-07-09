<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0"
                         xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0"
                         xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0"
                         xmlns:lticp="http://www.imsglobal.org/xsd/imslticp_v1p0"
                         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                         xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p2/imslticc_v1p2.xsd http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd">
    <blti:title>{{ $title }}</blti:title>
    <blti:launch_url>{{ $url }}</blti:launch_url>
    <blti:icon>{{ $icon }}</blti:icon>
    <blti:vendor>
        <lticp:code>pressbooks.education</lticp:code>
        <lticp:name>Pressbooks</lticp:name>
    </blti:vendor>
</cartridge_basiclti_link>