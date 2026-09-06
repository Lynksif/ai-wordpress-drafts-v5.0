# 9GG CLOUD source layout contract

Use this only when the explicit destination is `99ggcloud`. It translates the 9GG CLOUD editorial brief into the shared `g3ai-*` runtime without importing theme CSS, scripts, or page-builder markup.

## Identity

- Domain: `https://99ggcloud.com`
- Brand: `9GG CLOUD`
- Tagline: `Play Anywhere. Host Anything.`
- Root wrapper: `g3ai-site-99ggcloud`
- Preferred SEO provider: Rank Math
- Watermark: `99GGCLOUD.COM`

## Visual system

Use an esports cloud-lab dashboard: midnight navy background, cyan live data, violet GPU accents, lime healthy-status signals, and restrained orange latency warnings. Keep the design technical, readable, responsive, and credible rather than promotional.

## Article structure

Choose only the components that support the article type:

1. Hero with category, title, concise verdict, tested/updated date, author, and service class.
2. Test-context metadata: player region, connection type, device, display target, server region, plan, and test period when known.
3. Quick verdict in `g3ai-verdict`, separating strengths, limits, and the best-fit player.
4. Benchmark cards for measured latency, jitter, packet loss, FPS, resolution, encode/decode delay, startup time, uptime, or price. Do not invent missing values.
5. Comparison table with consistent test conditions and a clear source for every figure.
6. Region/server coverage, setup steps, configuration notes, latency troubleshooting, or buying guidance as appropriate.
7. Methodology and disclosure block that labels each figure as independently measured, provider-published, estimated, or unavailable.
8. Visible FAQ matching the page's questions and Schema where FAQ markup is used.

## Supported labels

- `g3ai-metric`: independently measured benchmark or clearly named numeric metric.
- `g3ai-source`: source/method label such as Independent test or Provider spec.
- `g3ai-latency`: latency-sensitive result or warning.
- `g3ai-tier`: plan, GPU tier, server class, or suitability tier.
- `g3ai-verdict`: concise editorial verdict with evidence and limits.

Use these labels only inside `g3ai-site-99ggcloud`.

## Evidence rules

- State test date, region, network, device, server location, settings, sample size, and tool when available.
- Never turn a provider claim into an independent result.
- Avoid false precision. If conditions are incomplete, label the result provisional.
- Separate cloud-gaming latency from game-server tick rate and GPU compute performance.
- Explain that real-player performance varies by route, congestion, distance, hardware, codec, and game workload.
- Use same-domain internal links only when URLs are known from live site data or the user's confirmed link map.

## Minimal wrapper

```html
<article class="g3ai-article g3ai-site-99ggcloud">
  <header class="g3ai-hero">
    <div>
      <span class="g3ai-eyebrow">9GG CLOUD TEST LAB</span>
      <h1>Article title</h1>
      <p class="g3ai-lead">Evidence-led summary for real players.</p>
      <div class="g3ai-meta">
        <span>Tested: YYYY-MM-DD</span>
        <span>Region: confirmed location</span>
        <span>Source: independent test or provider spec</span>
      </div>
    </div>
  </header>
  <section class="g3ai-verdict">
    <h2>Quick verdict</h2>
    <p>Summarize the result, best fit, and main limitation.</p>
  </section>
</article>
```

Do not include inline `style`, `script`, event handlers, or copied theme classes.
