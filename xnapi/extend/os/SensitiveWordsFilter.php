<?php  
  
namespace safe;  
  
class SensitiveWordsFilter  
{  
    protected $sensitiveWords = [];  
    protected $replacement = '*'; // 替换符号，可以是一个或多个字符  
  
    /**  
     * 构造函数，用于初始化敏感词词典  
     *  
     * @param array $words 敏感词数组  
     */  
    public function __construct(array $words = [])  
    {  
        $seqing = file_get_contents('../extend/safe/safewords/seqing');
        $words = explode(",",$seqing);
        $this->sensitiveWords = $words;  
        //$this->replacement = $seqing;
    }  
  
    /**  
     * 添加敏感词  
     *  
     * @param string $word 敏感词  
     */  
    public function addWord($word)  
    {  
        $this->sensitiveWords[] = $word;  
    }  
  
    /**  
     * 过滤文本中的敏感词  
     *  
     * @param string $text 需要过滤的文本  
     * @return string 过滤后的文本  
     */  
    public function filter($text)  
    {  
        foreach ($this->sensitiveWords as $word) {  
            // 简单的替换，可以使用正则表达式等更灵活的方法来处理边界情况  
            $pattern = '/(' . preg_quote($word, '/') . ')/i';  
            $text = preg_replace($pattern, str_repeat($this->replacement, mb_strlen($word)), $text);  
        }  
  
        return $text;  
    }  
  
    /**  
     * 设置替换符号  
     *  
     * @param string $replacement 替换符号  
     */  
    public function setReplacement($replacement)  
    {  
        $this->replacement = $replacement;  
    }  
}  
  
/*  
$filter = new SensitiveWordsFilter(['敏感词1', '敏感词2']);  
$filter->setReplacement('*');  
$text = "这是一段包含敏感词1和敏感词2的文本。";  
$filteredText = $filter->filter($text);  
echo $filteredText; // 输出：这是一段包含****和****的文本。*/



  //  $seqing = file_get_contents('../extend/safe/safewords/seqing');
//        $seqing = explode("\n",$seqing);