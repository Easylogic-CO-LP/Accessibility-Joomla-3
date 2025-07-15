<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.accessibility
 *
 * @copyright   (C) 2020 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * System plugin to add additional accessibility features to the administrator interface.
 *
 * @since  3.0.0
 */
class PlgSystemAccessibility extends JPlugin
{
    /**
     * Add the javascript for the accessibility menu
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function onBeforeCompileHead()
    {
        $section = $this->params->get('section', 'administrator');

        if ($section !== 'both' && JFactory::getApplication()->isAdmin() !== ($section === 'administrator')) {
            return;
        }

        // Get the document object.
        $document = JFactory::getDocument();

        if ($document->getType() !== 'html') {
            return;
        }

        // Are we in a modal?
        if (JFactory::getApplication()->input->get('tmpl', '', 'cmd') === 'component') {
            return;
        }

        // Load language file.
        $this->loadLanguage();

        // Determine if it is an LTR or RTL language
        $direction = JFactory::getLanguage()->isRtl() ? 'right' : 'left';

        // Detect the current active language
        $lang = JFactory::getLanguage()->getTag();

        /**
        * Add strings for translations in Javascript.
        * Reference  https://ranbuch.github.io/accessibility/
        */
        $options = array(
            'labels' => array(
                'menuTitle'           => JText::_('PLG_SYSTEM_ACCESSIBILITY_MENU_TITLE'),
                'increaseText'        => JText::_('PLG_SYSTEM_ACCESSIBILITY_INCREASE_TEXT'),
                'decreaseText'        => JText::_('PLG_SYSTEM_ACCESSIBILITY_DECREASE_TEXT'),
                'increaseTextSpacing' => JText::_('PLG_SYSTEM_ACCESSIBILITY_INCREASE_SPACING'),
                'decreaseTextSpacing' => JText::_('PLG_SYSTEM_ACCESSIBILITY_DECREASE_SPACING'),
                'invertColors'        => JText::_('PLG_SYSTEM_ACCESSIBILITY_INVERT_COLORS'),
                'grayHues'            => JText::_('PLG_SYSTEM_ACCESSIBILITY_GREY'),
                'underlineLinks'      => JText::_('PLG_SYSTEM_ACCESSIBILITY_UNDERLINE'),
                'bigCursor'           => JText::_('PLG_SYSTEM_ACCESSIBILITY_CURSOR'),
                'readingGuide'        => JText::_('PLG_SYSTEM_ACCESSIBILITY_READING'),
                'textToSpeech'        => JText::_('PLG_SYSTEM_ACCESSIBILITY_TTS'),
                'speechToText'        => JText::_('PLG_SYSTEM_ACCESSIBILITY_STT'),
                'resetTitle'          => JText::_('PLG_SYSTEM_ACCESSIBILITY_RESET'),
                'closeTitle'          => JText::_('PLG_SYSTEM_ACCESSIBILITY_CLOSE'),
            ),
            'icon' => array(
                'position' => array(
                    $direction => array(
                        'size'  => '0',
                        'units' => 'px',
                    ),
                ),
                'useEmojis' => $this->params->get('useEmojis') != 'false' ? true : false,
            ),
            'hotkeys' => array(
                'enabled'    => true,
                'helpTitles' => true,
            ),
            'textToSpeechLang' => array($lang),
            'speechToTextLang' => array($lang),
        );

        // Add script options for Joomla 3
        $document->addScriptDeclaration('
            window.Joomla = window.Joomla || {};
            window.Joomla.optionsStorage = window.Joomla.optionsStorage || {};
            window.Joomla.optionsStorage["accessibility-options"] = ' . json_encode($options) . ';
            window.Joomla.getOptions = window.Joomla.getOptions || function(key) {
                return window.Joomla.optionsStorage[key] || {};
            };
        ');

        // Load accessibility script dynamically when DOM is ready
        $document->addScriptDeclaration('
            function loadAccessibilityScript() {
                if (typeof Accessibility !== "undefined") return Promise.resolve();
                
                return new Promise(function(resolve, reject) {
                    var script = document.createElement("script");
                    script.src = "' . JUri::root() . 'plugins/system/accessibility/assets/accessibility.min.js";
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            }
            
            function initAccessibility() {
                if (!document.body) {
                    setTimeout(initAccessibility, 100);
                    return;
                }
                
                loadAccessibilityScript().then(function() {
                    setTimeout(function() {
                        new Accessibility(Joomla.getOptions("accessibility-options") || {});
                    }, 50);
                });
            }
            
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", initAccessibility);
            } else {
                initAccessibility();
            }
        ');
    }
}