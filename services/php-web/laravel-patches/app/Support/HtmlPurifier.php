<?php
namespace App\Support;

class HtmlPurifier
{
    public function clean(string $html, array $allowedTags = [], array $allowedAttributes = []): string
    {

        if (empty($allowedTags)) {
            return strip_tags($html);
        }
        

        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        $cleaned = strip_tags($html, $allowedTagsString);
        

        $cleaned = $this->cleanAttributes($cleaned, $allowedAttributes);
        
        return $cleaned;
    }
    
    private function cleanAttributes(string $html, array $allowedAttributes): string
    {

        $dangerousAttributes = [
            'onclick', 'onload', 'onerror', 'onmouseover', 'onmouseout',
            'onkeydown', 'onkeypress', 'onkeyup', 'onfocus', 'onblur',
            'javascript:', 'vbscript:'
        ];
        
        foreach ($dangerousAttributes as $attr) {
            $html = preg_replace("/{$attr}=['\"][^'\"]*['\"]/i", '', $html);
        }
        
        return $html;
    }
}
