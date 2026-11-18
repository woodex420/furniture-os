{{-- PHP counterpart of Reimagin ArticleText. Text remains escaped; no HTML is interpreted. --}}
<p class="text-lg text-muted-foreground leading-relaxed mb-10 pb-10 border-b border-border">{{ $props['summary'] }}</p>
<p class="text-base text-foreground/85 leading-[1.8] whitespace-pre-line">{{ $props['body'] }}</p>
