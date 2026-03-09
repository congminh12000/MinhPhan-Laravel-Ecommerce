<?php
/**
 * FooterRepo.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     guangda <service@guangda.work>
 * @created    2022-08-11 18:16:06
 * @modified   2022-08-11 18:16:06
 */

namespace Beike\Repositories;

class FooterRepo
{
    private const DEFAULT_FOOTER_SETTING = [
        'services' => [
            'enable' => false,
            'items'   => [],
        ],
        'content' => [
            'intro'   => [
                'logo'           => '',
                'text'           => [],
                'social_network' => [],
            ],
            'link1'   => [
                'title' => [],
                'links' => [],
            ],
            'link2'   => [
                'title' => [],
                'links' => [],
            ],
            'link3'   => [
                'title' => [],
                'links' => [],
            ],
            'contact' => [
                'telephone' => '',
                'address'   => [],
                'email'     => '',
            ],
        ],
        'bottom' => [
            'copyright' => [],
            'image'     => '',
        ],
    ];

    /**
     * 处理页尾编辑器数据
     *
     * @return array|mixed
     */
    public static function handleFooterData($footerSetting = [])
    {
        if (empty($footerSetting)) {
            $footerSetting = system_setting('base.footer_setting');
        }

        $footerSetting = array_replace_recursive(self::DEFAULT_FOOTER_SETTING, $footerSetting ?: []);
        $content         = $footerSetting['content'] ?? [];
        $contentLinkKeys = ['link1', 'link2', 'link3'];
        foreach ($contentLinkKeys as $contentLinkKey) {
            $links = $content[$contentLinkKey]['links'] ?? [];
            $links = collect($links)->map(function ($link) {
                return handle_link($link);
            })->toArray();
            $footerSetting['content'][$contentLinkKey]['links'] = $links;
        }

        return $footerSetting;
    }
}
