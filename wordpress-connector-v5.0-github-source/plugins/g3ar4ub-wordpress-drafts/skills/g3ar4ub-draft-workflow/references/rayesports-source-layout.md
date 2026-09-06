# RAYbet / Rayesports source layout contract

Use this only when the explicit destination is `rayesports`. It converts the supplied Rayesports source into the shared `g3ai-*` runtime without copying old inline styles or `rs-*` classes.

## Identity

- Domain: `https://rayesports.com`
- Visible brand: `RAYbet`
- Watermark: `RAYESPORTS.COM`
- Root wrapper: `g3ai-site-rayesports`
- Preferred SEO provider: `rank_math`
- Primary language: use the live WordPress health response; articles are normally Vietnamese.

## Visual and information hierarchy

1. Hero: freshness label, H1, short deck, and a `.g3ai-meta` row for update time, tournament/game, region, and match format when known.
2. Context card: a short editorial introduction that explains what is known, what is analysis, and when data was last checked.
3. Quick navigation: semantic `.g3ai-toc` links to the match table, game/league sections, key takeaways, and FAQ.
4. Data table: use `.g3ai-table-wrap`; one row per match or repeated-field item. Never merge multiple picks or claims into one line with `<br>`.
5. Detail sections: use `.g3ai-grid` or `.g3ai-grid-2` plus `.g3ai-card`; one match, team, tournament, or story angle per card.
6. FAQ: visible accordion questions and answers only when they materially help the search intent. FAQ Schema must match the visible text exactly.
7. Risk/status notice: time-sensitive schedules, odds, rosters, vetoes, and results must state the update time and source status. Odds-related content needs a visible entertainment/risk notice and must not promise an outcome.

## RAYbet components

- `.g3ai-tag`: short game or league tag such as LoL, Dota 2, CS2, Valorant.
- `.g3ai-odds`: a time-stamped numeric price only when supplied or verified; never invent one.
- `.g3ai-confidence`: a clearly labeled editorial confidence indicator, not a guarantee.
- `.g3ai-picks`: a flex row containing separate labels or links; every independent recommendation must be a separate element.
- `.g3ai-callout.g3ai-warning`: responsible-risk notice or unverified/time-sensitive warning.

## Safe semantic template

```html
<article class="g3ai-article g3ai-site-rayesports">
  <header class="g3ai-hero">
    <span class="g3ai-eyebrow">RAYbet Esports Update</span>
    <h1>Article title</h1>
    <p class="g3ai-lead">A concise Vietnamese deck that states the search intent and freshness.</p>
    <div class="g3ai-meta">
      <span>Updated: verified time</span>
      <span>Game or tournament</span>
      <span>BO format when known</span>
    </div>
  </header>
  <section class="g3ai-section" id="overview">
    <h2>Context and key update</h2>
    <p>Separate verified facts from editorial analysis.</p>
  </section>
  <nav class="g3ai-toc" aria-label="Mục lục">
    <ol>
      <li><a href="#match-table">Bảng trận đấu</a></li>
      <li><a href="#analysis">Phân tích</a></li>
      <li><a href="#faq">Câu hỏi thường gặp</a></li>
    </ol>
  </nav>
  <section class="g3ai-section" id="match-table">
    <h2>Bảng trận đấu</h2>
    <div class="g3ai-table-wrap">
      <table>
        <thead>
          <tr><th>Giờ</th><th>Giải</th><th>Trận đấu</th><th>Thể thức</th><th>Trạng thái</th></tr>
        </thead>
        <tbody>
          <tr><td>Verified time</td><td><span class="g3ai-tag">Game</span> League</td><td>Team A vs Team B</td><td>BO3</td><td>Scheduled</td></tr>
        </tbody>
      </table>
    </div>
  </section>
  <section class="g3ai-section" id="analysis">
    <h2>Phân tích</h2>
    <div class="g3ai-grid g3ai-grid-2">
      <div class="g3ai-card">
        <h3>One match or story angle</h3>
        <ul class="g3ai-checklist">
          <li>One verified fact.</li>
          <li>One clearly labeled inference.</li>
        </ul>
      </div>
    </div>
  </section>
  <aside class="g3ai-callout g3ai-warning">
    <h2>Lưu ý dữ liệu và rủi ro</h2>
    <p>Schedules, rosters, vetoes, results, and odds can change. Verify current information and do not treat analysis as a guaranteed outcome.</p>
  </aside>
</article>
```

## Editorial rules

- Prioritize esports news and useful match context over promotional language.
- Use `NewsArticle` for timely reporting and `BlogPosting` for evergreen explainers.
- Use same-domain Rayesports internal links discovered from live/user-provided URLs. Do not invent category paths.
- Attribute current schedules, results, roster moves, quotes, and odds to verified sources.
- Do not claim a “safe bet,” guaranteed win, or certainty based only on odds.
