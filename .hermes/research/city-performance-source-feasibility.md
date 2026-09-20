# City Performance Source Feasibility Reconnaissance

## Scope and constraints

Low-volume reconnaissance only. Tested public, non-transactional pages for Jakarta, Bandung, Surabaya, Medan, and Makassar. No login, OTP, private token, undocumented endpoint replay, order flow, or seat selection was used. Evidence below is from normal public HTML/search extraction and representative pages; it is not a legal opinion.

## Executive conclusion

**Cinema XXI is the strongest Phase 0 candidate for a read-only adapter, subject to written permission/terms review before commercial automation.** Its public mobile schedule pages expose city coverage, cinema IDs, film IDs, dates, showtimes, cinema address/coordinates, duration, rating, and prices in server-rendered HTML. It covered all five target cities in representative pages.

**CGV is technically suitable as a public HTML source but legally not cleared for third-party reuse.** Its public cinema schedule pages expose stable cinema IDs, date navigation, cinema/address, auditorium class, showtimes, price, and apparent seat counts; however its Legal Terms state that third-party use of CGV content without consent is illegal. Treat as **blocked for production reuse absent permission**.

**Cinépolis is technically plausible but less suitable for an initial adapter.** Public cinema pages expose UUID-like cinema IDs, movie UUIDs, dates, showtimes, format, price, and cinema metadata. Robots explicitly allow public content and disallow transactional paths, but no explicit commercial reuse permission was found in this reconnaissance. Coverage and city-level sampling were not completed for all five target cities. Treat as **permission/terms unclear; fallback candidate only**.

**Downstream aggregators were not selected.** Search surfaced Jadwalnonton and IMDb showtime pages, but no clear Indonesian commercial-reuse permission or stable, complete, city-level coverage was established. They should not be used without terms/licensing verification.

## Source findings

### 1. Cinema XXI / 21 Cineplex — recommended Phase 0 candidate, pending permission

- Public city selector: `https://m.21cineplex.com/gui.list_city.php`
- Representative schedule shape: `https://m.21cineplex.com/gui.schedule.php?sid=&find_by=1&cinema_id=UPGTSM&movie_id=`
- Stable identifiers observed:
  - Cinema IDs in URL, e.g. `JKTCITR`, `BDGBSM`, `SBYPAMA`, `MDNCEPO`, `UPGTSM`.
  - Film IDs in links/images, e.g. `26REVL`, `16UBUN`, `16MPEA`.
  - Movie detail URLs are public, e.g. `/gui.movie_details.php?...movie_id=26REVL`.
- Public fields observed in HTML:
  - Cinema name, address, phone, Google Maps coordinates.
  - Film title, film ID, format/rating, duration.
  - Date and multiple showtimes per film.
  - Price.
- Target-city evidence:
  - Jakarta: Citra XXI (`JKTCITR`) showed Resident Evil, Urang Bunian, Agensi Rumah Tangga, etc., with date `20-09-2026` and showtimes.
  - Bandung: TSM XXI (`BDGBSM`) showed film/date/showtimes and cinema coordinates.
  - Surabaya: Pakuwon Mall XXI (`SBYPAMA`) showed film/date/showtimes and coordinates.
  - Medan: Centre Point XXI (`MDNCEPO`) showed film/date/showtimes and coordinates.
  - Makassar: TSM XXI (`UPGTSM`) showed film/date/showtimes and coordinates.
- Coverage: city selector visibly listed Jakarta, Bandung, Makassar, Medan, Surabaya and many additional Indonesian cities. This is good evidence of broad chain coverage, not a census of currently operating cinemas.
- Normal HTML/browser feasibility: confirmed. Representative schedule pages were readable without authentication and included the needed fields in HTML.
- Robots/terms: `https://m.21cineplex.com/robots.txt` and `https://www.21cineplex.com/robots.txt` returned 404 rather than a robots policy. A guessed `/terms-of-use` route redirected to a login page; no usable public terms text was confirmed. Commercial automated reuse is therefore **unclear**, not presumed permitted.
- Constraints/risks:
  - Some showtime anchors are rendered as `#` links and may initiate ticket selection if clicked; an adapter should read text only and never click/submit.
  - One extracted page showed some times concatenated in text (`12:4514:40`) when links were absent, so parser robustness is required.
  - Schedule date availability is dynamic and may vary by cinema.
- Assessment: **Best technical fit; legal clearance required before production or commercial monitoring.**

### 2. CGV Indonesia — strong technical fit, not cleared by published legal terms

- Representative pages:
  - Jakarta: `https://www.cgv.id/schedule/cinema/002?name=grand-indonesia`
  - Bandung: `https://www.cgv.id/schedule/cinema/029?name=23-paskal-shopping-center`
  - Surabaya: `https://www.cgv.id/schedule/cinema/048?name=bg-junction`
  - Medan: `https://www.cgv.id/schedule/cinema/024?name=focal-point`
  - Makassar: `https://www.cgv.id/schedule/cinema/072?name=panakkukang-square`
- Stable identifiers observed: numeric cinema IDs in `/schedule/cinema/{id}`. Date-specific routes also appeared, e.g. `/schedule/cinema/024/2026-09-09`.
- Public fields observed:
  - Cinema name and address.
  - Date navigation (today and future dates).
  - Auditorium/class (Regular, Velvet, Gold Class, ScreenX, etc.).
  - Showtime intervals, duration implied by start-end interval, price, and seat counts.
- Target-city evidence: one representative public page each was successfully extracted for all five target cities above.
- Robots: `https://www.cgv.id/robots.txt` returned `User-Agent: *`, `Allow: /`, with disallow only for `/App_Data` and `/bin`.
- Legal terms: `https://www.cgv.id/content/legal_term` states, among other points, “Usage by third parties without CGV Cinemas' consent is considered an illegal act.” It also says CGV only sells official tickets through official channels/partners. This is a material blocker for unpermissioned commercial reuse even though robots allows crawling.
- Assessment: **Do not use for Phase 0 production adapter without explicit CGV consent/license.** Public HTML feasibility is high; permission status is negative/blocked based on the published legal page.

### 3. Cinépolis Indonesia — technically viable, permission unclear

- Representative cinema page: `https://www.cinepolis.co.id/cinema/living-plaza-balikpapan/bcc1854c-1de8-4a52-a53a-0dc5e55d5953/`
- Public movie index: `https://www.cinepolis.co.id/`
- Sitemap: `https://www.cinepolis.co.id/sitemap.xml`
- Stable identifiers observed:
  - Cinema UUID-like IDs in public cinema URLs.
  - Movie UUID-like IDs in public movie URLs and sitemap entries.
- Public fields observed on cinema page:
  - Cinema name, address, opening hours/contact email.
  - Movie title, genre, runtime, year, rating, language.
  - Screen class/format, price, date options, showtimes.
- Robots: `https://www.cinepolis.co.id/robots.txt` returned `User-agent: *`, `Allow: /`, and disallowed transactional paths `/account/`, `/order/`, `/payment/`, `/pg/`, `/helper/`, `/handlers/`, `/fnb/cart/`; sitemap was declared. This supports a clear distinction between public content and transaction paths.
- Terms/reuse: no explicit commercial scraping/reuse permission or prohibition was located during this limited pass. Robots is not a license. **Commercial reuse remains unclear**.
- Coverage: sitemap showed broad public movie/cinema URL coverage, including examples in Jakarta and Medan; this reconnaissance did not fully verify a representative schedule page for each of the five target cities.
- Assessment: **Possible secondary adapter after terms/permission review; not the first Phase 0 source.**

## Field/ID comparison

| Source | City | Cinema ID | Film ID | Date | Showtime | Price | Location | Public HTML | Permission posture |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---|
| Cinema XXI | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Confirmed | Unclear; terms not found, robots 404 |
| CGV | Yes | Yes | Not separately confirmed on schedule page | Yes | Yes | Yes | Address confirmed | Confirmed | Blocked absent consent by legal terms |
| Cinépolis | Partial verified | Yes (UUID) | Yes (UUID) | Yes | Yes | Yes | Yes | Confirmed | Unclear; robots permits public paths |

## Phase 0 recommendation

1. **Proceed only with a Cinema XXI feasibility adapter spike after obtaining written permission or confirming applicable official terms.** Keep it read-only: fetch public schedule pages, parse stable cinema/film IDs and visible text, and do not follow ticket/seat-selection actions.
2. **Do not use CGV without explicit consent.** Its public HTML is excellent, but its legal page expressly rejects third-party content use without consent.
3. Keep Cinépolis as a **candidate fallback** pending a focused city-coverage and terms review.
4. Do not treat TIX, booking/seat flows, or downstream aggregators as acceptable substitutes under the stated constraints.

## Evidence status and limitations

- Confirmed live/public evidence: representative pages and fields described above were retrieved successfully.
- Inference: “broad coverage” for XXI/Cinépolis is based on visible city lists/sitemaps, not a complete inventory.
- No source was found that both (a) clearly grants commercial automated reuse and (b) offers all required city-level fields without permission review.
- This is a feasibility reconnaissance, not legal advice. Obtain source-owner approval/licensing before production monitoring or commercial reuse.
