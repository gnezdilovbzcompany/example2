<?php defined('BASEPATH') or die('No direct script access allowed.');?>
<?php

$fava = ($__this->oj->basket->data) ? (array) $__this->oj->basket->data->fav : array();
$show_pager = (isset($show_pager) AND $show_pager);
$lazy_image = (isset($lazy_image)) ? $lazy_image : !$__this->request->is_ajax();

/*
*   Nacteme varianty pro produkty ve vypise
*
*/
$prod_ids = array();
foreach ($list as $item)
{
    $prod_ids[] = $item->id;
}
$id2variants = array();
if (count($prod_ids) > 0)
{
    $where = array(
        'parent_product_id' => $prod_ids,
        'is_variant' => 1,
        'is_published' => 1,
        'is_purchasable' => 1,
    );
    $args = array(
        'order_by' => 'title',
        'currency' => $__this->oj->store->currency_code,
    );
    $variants = Com\OJ\Product::find_all($where, $args);
    if ($variants AND count($variants) > 0)
    {
        foreach ($variants as $item)
        {
            $id2variants[$item->parent_product_id][] = $item;
        }
    }
}

$item_ids = array();
$vix = 0;
$ix = 0;
$str = '';
foreach ($list as $item)
{
    $ix++;
    $img = $item->image->find_first();
    $link = $item->link();
    $item_ids[] = $item->id;
    $is_fav = in_array($item->id, $fava);

    /*
    *   Stitky
    *
    */
    $tags = [];
    if ($item->test_ctag_short_name('SALE'))
    {
        $tags['sale'] = '<span class="tag sale"><span class="txt">Výprodej</span></span>';
    }
    if ($item->test_ctag_short_name('NEW'))
    {
        $tags['new'] = '<span class="tag new"><span class="txt">Novinka</span></span>';
    }

    $dlr = $__this->oj->product_pricing($item)->find_discount_rule();
    $is_discounted = FALSE;
    $price_retail = $item->price_retail(FALSE, array(PHP_ROUND_HALF_UP, 2));
    $price_sale0 = $__this->oj->product_pricing($item)->price_incl_vat(FALSE, array(PHP_ROUND_HALF_UP, 2));
    if ($dlr)
    {
        if ((float) $dlr->value > 0.0)
        {
            $price_retail = $item->sale_price_incl_vat(FALSE, array(PHP_ROUND_HALF_UP, 2));
        }

        if ($price_retail > 0)
        {
            $off_pct = abs((1 - ($price_sale0 / $price_retail)) * 100);
            $tags['dis'] = '<span class="tag discount"><span class="txt">'.__('Sleva').' '.round(min(99, $off_pct)).'%</span></span>';
            $is_discounted = TRUE;
        }
    }

    $str_tag = '';
    $cnt_tags = count($tags);
    $str_tag = ($cnt_tags > 0) ? '<span class="tagset tc'.$cnt_tags.'">'.implode($tags).'</span>' : '';

    $str .= '<div class="item nx'.$ix.(($ix%4 === 0) ? ' nth4n' : '').'">'
        .'<div class="box">'
        .$str_tag
        .'<div class="img'.(($img) ? '' : ' empty').'">'
        .'<span class="t">'
        .(($img)
            ?   (($lazy_image)
                ?    '<img data-original="'.$img->link(array('resize' => array('360x390', 'contain'))).'" alt="'.$img->name.'" title="'.htmlspecialchars($img->note).'" class="lazy">'
                .'<noscript><img src="'.$img->link(array('resize' => array('360x390', 'contain'))).'" alt="'.$img->name.'" title="'.htmlspecialchars($img->note).'"></noscript>'
                :   '<img src="'.$img->link(array('resize' => array('360x390', 'contain'))).'" alt="'.$img->name.'" title="'.htmlspecialchars($img->note).'">'
            )
            :   ''
        )
        .'</span>'
        .'<a href="'.$link.'" class="link"></a>'
        .'</div>'
        .'<div class="c">'
        .'<h2 class="title"><a href="'.$link.'">'.$item->title().'</a></h2>'
        .'<p class="pricing">'
        .(($is_discounted)
            ?   '<span class="pcret">'.Com\OJ\Helper\Fmt::price_curr($price_retail, '%s %s', TRUE, NULL, NULL, $item->currency_code).'</span>'
            :   ''
        )
        .'<span class="pc">'.$__this->oj->product_pricing($item)->price_incl_vat_curr().'</span>'
        .'</p>'
        .'</div>'
        .'<div class="m">'
        .'<form action="'.URL::link('{uri}').'" method="post" class="pbadd fbas">'
        .'<button type="submit" name="bap['.$item->id.']" class="button">+ do košíku</button>'
        .'<input type="hidden" name="qty['.$item->id.']" value="1"'
        .' data-prod-id="'.$item->id.'"'
        .' data-prod-titlegl="'.htmlspecialchars($item->feeds_data->productname ?: $item->title).'"'
        .' data-prod-price1="'.number_format($item->sale_price_incl_vat(FALSE, [PHP_ROUND_HALF_UP, 2]), 2, '.', '').'"'
        .' data-prod-currency="'.$item->currency_code.'"'
        .' />'
        .'</form>'
        .'</div>'
        .'</div>'
        .'</div>'
    ;
}

echo '<div class="prodlist storeitemslist">'
    .$str
    .'</div>'
;

if ($show_pager)
{
    echo $__this->view('eshop/catalog/list_pager')
        ->set('paging', $list->paging())
        ->set('found_rows', $list->found_rows)
        ->set('per_page', $list->limit)
        ->bind('show_empty', $show_empty)
    ;
}


$allow_conversions = (isset($allow_conversions)) ? $allow_conversions : FALSE;
if ($allow_conversions AND count($list))
{
    $searchq = (isset($list_args['fulltext'])) ? $list_args['fulltext'] : '';
    (isset($is_product_detail)) OR $is_product_detail = FALSE;
    (isset($is_homepage)) OR $is_homepage = FALSE;
    (isset($active_category)) OR $active_category = FALSE;

    /*
    *   Facebook pixel code
    *
    */
    $fbpix_id = $__this->oj->store->settings->get('facebook_pixel_id');
    if ($fbpix_id != '' AND !$is_homepage)//$is_product_detail)
    {
        (isset($active_category)) OR $active_category = FALSE;
        $param = array(
            "content_type: 'product_group'",
        );
        ($active_category) AND $param[] = "content_category: '".$active_category->title()."'";
        ($item_ids) AND $param[] = "content_ids: ['".implode("','", $item_ids)."']";

        if (1)
        {
            ($searchq == '') OR $param[] = sprintf("search_string: '%s'", urlencode($searchq));

            $x = $__this->param->js_track ?: '';
            $x .= "<!-- facebook pixel code -->".PHP_EOL
                ."<script>".PHP_EOL
                ."fbq('track', 'Search', {".PHP_EOL
                .implode(",".PHP_EOL, $param).PHP_EOL
                ."});".PHP_EOL
                ."</script>".PHP_EOL
                .'<noscript><img src="https://www.facebook.com/tr?'
                .'id='.$fbpix_id
                .'&ev=ViewContent'
                .(($active_category) ? '&cd[content_category]='.$active_category->title() : '')
                .'&cd[content_type]=product_group'
                .(($searchq == '') ? '' : '&cd[search_string]='.urlencode($searchq))
                .(($item_ids) ? '&cd[content_ids]='.implode(',', $item_ids) : '')
                .'&noscript=1" height="1" width="1" style="display:none"/>'
                .'</noscript>'.PHP_EOL
                ."<!-- end of facebook pixel code -->"
                .PHP_EOL
            ;

            $__this->param->js_fbpixel = TRUE;
            $__this->param->js_track = $x;
        }
    }

    /*
    *   Google AdWords remarketing
    *
    */
    $gad_code = $__this->oj->store->settings->get('google_adwords_remarketing_id');
    if ($gad_code != '' AND count($item_ids) > 0)
    {
        $pagetype = ($is_homepage) ? 'home' : (($searchq) ? 'searchresults' : 'category');

        $altlink = '//googleads.g.doubleclick.net/pagead/viewthroughconversion/'.$gad_code.'/?value=0&amp;guid=ON&amp;script=0'
            .'&amp;data.ecomm_prodid='.implode('&amp;data.ecomm_prodid=', $item_ids)
            .'&amp;data.ecomm_pagetype='.$pagetype
            .'&amp;data.ecomm_totalvalue=0'
        ;

        $x = $__this->param->js_track ?: '';
        $x .= '<script type="text/javascript">'.PHP_EOL
            .'/'.'* <![CDATA[ *'.'/'.PHP_EOL
            .'var google_conversion_id = '.$gad_code.';'.PHP_EOL
            .'var google_custom_params = {'.PHP_EOL
            .'ecomm_prodid: ["'.implode('","', $item_ids).'"],'.PHP_EOL
            .'ecomm_pagetype: "'.$pagetype.'",'.PHP_EOL
            .'ecomm_totalvalue: 0'.PHP_EOL
            .'};'.PHP_EOL
            .'var google_remarketing_only = true;'.PHP_EOL
            .'/'.'* ]]> *'.'/'.PHP_EOL
            .'</script>'.PHP_EOL
            .'<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js"></script>'.PHP_EOL
            .'<noscript>'.PHP_EOL
            .'<div style="display:inline;">'.PHP_EOL
            .'<img height="1" width="1" style="border-style:none;" alt="" src="'.$altlink.'"/>'.PHP_EOL
            .'</div>'.PHP_EOL
            .'</noscript>'.PHP_EOL
        ;

        $__this->param->js_track = $x;
    }
}
