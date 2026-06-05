<?php
Omurga::addAction('omurga.post.publish', function($post){
    // SEO önbelleği yenileme gibi işlemler burada yapılır.
});
Omurga::schedule('ornek.seo.daily', 'daily');
