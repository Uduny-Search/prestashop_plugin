{*
 * NOTICE OF LICENSE
 *
 * This source file is subject to a trade license awared by
 * PT. Jawa Kreatif Design.
 *
 * Any use, reproduction, modification or distribution
 * of this source file without the written consent of
 * PT. Jawa Kreatif Design It Is prohibited.
 *
 *  @author    UnusualDope <info@unusualdope.com>
 *  @copyright 2022 PT. Jawa Kreatif Design
 *  @license   Commercial license
*}

{if $nav_menu}
    <style>
        #content.bootstrap .panel.ud-nav {
            padding: 0;
        }
        .panel.ud-nav .menu-list,
        .panel.ud-nav .menu-list > div {
            display: flex;
        }
        .panel.ud-nav .menu-list {
            justify-content: space-between;
        }
        .panel.ud-nav .menu-list > div > div {
            padding: 10px 20px;
        }
        .panel.ud-nav .menu-list a.disabled {
            color: #888;
            pointer-events: none;
        }
        .panel.ud-nav .clear-cache {
            position: relative;
        }
        .panel.ud-nav .clear-cache:after {
            content: "";
            width: 10px;
            height: 10px;
            position: absolute;
            top: 0;
            right: 0;
            border-radius: 50%;
            background-color: #ff4a4a;
            box-shadow: 0px 0px 3px 0px #888;
        }
        .panel.ud-nav .clear-cache.cache-active:after {
            background-color: #65d929;
        }
    </style>

    <div class="panel ud-nav">
        <div class="menu-list">
            <div class="submenu-left">
                {foreach from=$nav_menu.left item=menu}
                    <div>
                        <a class="toolbar_btn pointer {if isset($menu.classes)}{$menu.classes}{/if} {if $menu.disabled}disabled{/if}" href="{$menu.href}" title="{if isset($menu.title)}{$menu.title}{else}{$menu.desc}{/if}">
                            <i class="{$menu.icon}"></i>
                            <div>{$menu.desc}</div>
                        </a>
                    </div>
                {/foreach}
            </div>
            <div class="submenu-right">
                {foreach from=$nav_menu.right item=menu}
                    <div>
                        <a class="toolbar_btn pointer {if isset($menu.classes)}{$menu.classes}{/if} {if $menu.disabled}disabled{/if}" href="{$menu.href}" title="{if isset($menu.title)}{$menu.title}{else}{$menu.desc}{/if}">
                            <i class="{$menu.icon}"></i>
                            <div>{$menu.desc}</div>
                        </a>
                    </div>
                {/foreach}
            </div>
        </div>
    </div>
    {if count($nav_menu.alert) > 0}
        {foreach from=$nav_menu.alert item=alert}
            <div class="alert alert-{$alert.type}" id="{$alert.id}-{$alert.type}" style="display:{if isset($alert.display) && $alert.display}block{else}none{/if};">
                {$alert.message}
            </div>
        {/foreach}
    {/if}
{/if}