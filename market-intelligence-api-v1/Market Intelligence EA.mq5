//+------------------------------------------------------------------+
//|                                   Market Intelligence EA.mq5    |
//|                           Monthly Timeframe Observation Log     |
//+------------------------------------------------------------------+
#property copyright "Market Intelligence EA"
#property version   "1.00"
#property description "Reads all available monthly candles and narrates what price is doing."

input int InpLookbackMonths = 24;
input int InpShortMAPeriod  = 6;
input int InpLongMAPeriod   = 12;

int OnInit()               { AnalyseMonthly(); return INIT_SUCCEEDED; }
void OnDeinit(const int r) {}
void OnTick()              {}

//+------------------------------------------------------------------+
double CalcSMA(const MqlRates &rates[], int start, int period)
{
   if(period <= 0) return 0;
   double s = 0;
   for(int i = start; i < start + period; i++) s += rates[i].close;
   return s / period;
}

double CalcStdDev(const double &arr[], int start, int count)
{
   if(count < 2) return 0;
   double mean = 0;
   for(int i = start; i < start + count; i++) mean += arr[i];
   mean /= count;
   double v = 0;
   for(int i = start; i < start + count; i++) v += (arr[i] - mean) * (arr[i] - mean);
   return MathSqrt(v / count);
}

string MonthName(int m)
{
   string n[] = {"","January","February","March","April","May","June",
                 "July","August","September","October","November","December"};
   return (m >= 1 && m <= 12) ? n[m] : "?";
}

string QuarterLabel(int q)
{
   string n[] = {"","Q1 (Jan-Mar)","Q2 (Apr-Jun)","Q3 (Jul-Sep)","Q4 (Oct-Dec)"};
   return (q >= 1 && q <= 4) ? n[q] : "?";
}

//+------------------------------------------------------------------+
void AnalyseMonthly()
{
   int totalBars = iBars(_Symbol, PERIOD_MN1);
   if(totalBars < 6) { Print("Not enough monthly candles."); return; }

   int copyCount = MathMin(totalBars, 600);
   MqlRates rates[];
   ArraySetAsSeries(rates, true); // index 0 = most recent (still forming)
   int copied = CopyRates(_Symbol, PERIOD_MN1, 0, copyCount, rates);
   if(copied <= 0) { Print("CopyRates failed. Error: ", GetLastError()); return; }

   //--- Basic prices
   double currentClose = rates[0].close;
   double currentOpen  = rates[0].open;
   double currentHigh  = rates[0].high;
   double currentLow   = rates[0].low;
   double prevClose    = rates[1].close;
   double prevOpen     = rates[1].open;
   datetime firstTime  = rates[copied - 1].time;
   double   firstOpen  = rates[copied - 1].open;

   //--- All-time high / low
   double allTimeHigh = 0, allTimeLow = DBL_MAX;
   int athBar = 0, atlBar = 0;
   for(int i = 0; i < copied; i++)
   {
      if(rates[i].high > allTimeHigh) { allTimeHigh = rates[i].high; athBar = i; }
      if(rates[i].low  < allTimeLow)  { allTimeLow  = rates[i].low;  atlBar = i; }
   }

   double netMove    = currentClose - firstOpen;
   double netMovePct = (firstOpen > 0) ? (netMove / firstOpen) * 100.0 : 0;

   //--- Structure: recent half vs older half
   int lookback = MathMin(InpLookbackMonths, copied - 1);
   int half     = MathMax(lookback / 2, 2);
   double recentHigh = 0, recentLow = DBL_MAX;
   double olderHigh  = 0, olderLow  = DBL_MAX;
   for(int i = 1; i <= half; i++)
   {
      if(rates[i].high > recentHigh) recentHigh = rates[i].high;
      if(rates[i].low  < recentLow)  recentLow  = rates[i].low;
   }
   for(int i = half + 1; i <= lookback; i++)
   {
      if(rates[i].high > olderHigh) olderHigh = rates[i].high;
      if(rates[i].low  < olderLow)  olderLow  = rates[i].low;
   }

   //--- Moving averages
   double shortMA   = CalcSMA(rates, 1, MathMin(InpShortMAPeriod, copied - 1));
   double longMA    = CalcSMA(rates, 1, MathMin(InpLongMAPeriod,  copied - 1));
   double vsShortMA = currentClose - shortMA;
   double vsLongMA  = currentClose - longMA;

   //--- Recent 6-bar candle sequence
   int recentWindow = MathMin(6, copied - 1);
   int upCount = 0, downCount = 0;
   string recentBars = "";
   for(int i = recentWindow; i >= 1; i--)
   {
      bool up = (rates[i].close > rates[i].open);
      if(up) upCount++; else downCount++;
      recentBars += (up ? "UP" : "DN");
      if(i > 1) recentBars += " -> ";
   }

   //--- Range position (within lookback)
   double rangeHigh  = MathMax(recentHigh, olderHigh);
   double rangeLow   = MathMin(recentLow,  olderLow);
   double rangeSize  = rangeHigh - rangeLow;
   double pctInRange = (rangeSize > 0) ? ((currentClose - rangeLow) / rangeSize) * 100.0 : 50.0;

   //--- ATH / ATL distances
   double pctFromATH = (allTimeHigh > 0) ? ((allTimeHigh - currentClose) / allTimeHigh) * 100.0 : 0.0;
   double pctFromATL = (allTimeLow  > 0) ? ((currentClose - allTimeLow)  / allTimeLow)  * 100.0 : 0.0;

   //--- Current bar shape
   double barRange       = currentHigh - currentLow;
   double bodySize       = MathAbs(currentClose - currentOpen);
   double bodyPct        = (barRange > 0) ? (bodySize / barRange) * 100.0 : 0.0;
   double upperWick      = currentHigh - MathMax(currentClose, currentOpen);
   double lowerWick      = MathMin(currentClose, currentOpen) - currentLow;
   double moveFromPrev   = currentClose - prevClose;
   double moveFromPrevPct= (prevClose > 0) ? (moveFromPrev / prevClose) * 100.0 : 0.0;

   //===================================================================
   // PERCENTILE RANKING
   //===================================================================
   int totalCloses = copied - 1;
   double closes[];
   ArrayResize(closes, totalCloses);
   for(int i = 0; i < totalCloses; i++) closes[i] = rates[i + 1].close;
   ArraySort(closes);
   int closeRank = 0;
   for(int i = 0; i < totalCloses; i++) if(closes[i] < currentClose) closeRank++;
   double percentile = (totalCloses > 0) ? ((double)closeRank / totalCloses) * 100.0 : 50.0;

   //===================================================================
   // DRAWDOWN ANALYSIS
   //===================================================================
   double runHigh        = rates[copied - 1].high;
   double peakForDD      = runHigh;
   double ddLow          = rates[copied - 1].low;
   bool   inDD           = false;
   double largestDD      = 0;
   double sumDDs         = 0;
   int    ddCount        = 0;
   int    deepDDCount    = 0;
   double largestMonthDrop = 0;

   for(int i = copied - 2; i >= 1; i--)
   {
      if(rates[i + 1].close > 0)
      {
         double mc = ((rates[i].close - rates[i + 1].close) / rates[i + 1].close) * 100.0;
         if(mc < 0 && -mc > largestMonthDrop) largestMonthDrop = -mc;
      }
      if(rates[i].high > runHigh)
      {
         if(inDD && peakForDD > 0)
         {
            double ddPct = (peakForDD - ddLow) / peakForDD * 100.0;
            if(ddPct > 3.0)
            {
               sumDDs += ddPct; ddCount++;
               if(ddPct > largestDD) largestDD = ddPct;
               if(pctFromATH > 1.0 && ddPct >= pctFromATH) deepDDCount++;
            }
         }
         runHigh = rates[i].high; peakForDD = runHigh; ddLow = rates[i].low; inDD = false;
      }
      else { inDD = true; if(rates[i].low < ddLow) ddLow = rates[i].low; }
   }
   double avgDD = (ddCount > 0) ? sumDDs / ddCount : 0;

   //===================================================================
   // TREND STRENGTH — consecutive HH / HL / LH / LL (all independent)
   //===================================================================
   int consHH = 0, consHL = 0, consLH = 0, consLL = 0;
   for(int i = 1; i < copied - 1; i++) { if(rates[i].high > rates[i+1].high) consHH++; else break; }
   for(int i = 1; i < copied - 1; i++) { if(rates[i].low  > rates[i+1].low)  consHL++; else break; }
   for(int i = 1; i < copied - 1; i++) { if(rates[i].high < rates[i+1].high) consLH++; else break; }
   for(int i = 1; i < copied - 1; i++) { if(rates[i].low  < rates[i+1].low)  consLL++; else break; }

   //===================================================================
   // VOLATILITY REGIME
   //===================================================================
   int retCount = MathMin(copied - 2, 120);
   double returns[];
   ArrayResize(returns, retCount);
   for(int i = 0; i < retCount; i++)
      returns[i] = (rates[i+1].close > 0) ? ((rates[i].close - rates[i+1].close) / rates[i+1].close) * 100.0 : 0;
   int    recentVolWin  = MathMin(12, retCount);
   double currentVol    = CalcStdDev(returns, 0, recentVolWin);
   double historicalVol = CalcStdDev(returns, 0, retCount);
   double maxVol = 0, minVol = DBL_MAX;
   for(int i = 0; i <= retCount - 12; i++)
   {
      double v = CalcStdDev(returns, i, 12);
      if(v > maxVol) maxVol = v;
      if(v < minVol) minVol = v;
   }
   double volPercentile = (maxVol - minVol > 0) ? ((currentVol - minVol) / (maxVol - minVol)) * 100.0 : 50.0;

   //===================================================================
   // 24-MONTH EXTREMES
   //===================================================================
   int    rangeMonths  = MathMin(24, copied - 1);
   double high24m      = 0, low24m = DBL_MAX;
   int    high24mBar   = 0, low24mBar = 0;
   for(int i = 1; i <= rangeMonths; i++)
   {
      if(rates[i].high > high24m) { high24m = rates[i].high; high24mBar = i; }
      if(rates[i].low  < low24m)  { low24m  = rates[i].low;  low24mBar  = i; }
   }
   double distFromHigh24m = (high24m > 0)  ? ((high24m - currentClose) / high24m) * 100.0  : 0;
   double distFromLow24m  = (low24m  > 0)  ? ((currentClose - low24m)  / low24m)  * 100.0  : 0;
   double range24m        = high24m - low24m;
   double pctIn24mRange   = (range24m > 0) ? ((currentClose - low24m)  / range24m) * 100.0 : 50.0;

   //===================================================================
   // SEASONAL ANALYSIS — SAME MONTH & SAME QUARTER
   //===================================================================
   MqlDateTime nowDt;
   TimeToStruct(TimeCurrent(), nowDt);
   int currentMonth   = nowDt.mon;
   int currentQuarter = (currentMonth - 1) / 3 + 1;

   // --- Same month ---
   int    smCount = 0, smUp = 0, smDn = 0;
   double smSum = 0, smBest = -DBL_MAX, smWorst = DBL_MAX;
   int    smBestYear = 0, smWorstYear = 0;

   for(int i = 1; i < copied; i++) // skip i=0 = still-forming bar
   {
      MqlDateTime dt;
      TimeToStruct(rates[i].time, dt);
      if(dt.mon != currentMonth) continue;

      double pct = (rates[i].open > 0) ? ((rates[i].close - rates[i].open) / rates[i].open) * 100.0 : 0;
      smCount++;
      smSum += pct;
      if(pct >= 0) smUp++; else smDn++;
      if(pct > smBest)  { smBest  = pct; smBestYear  = dt.year; }
      if(pct < smWorst) { smWorst = pct; smWorstYear = dt.year; }
   }
   double smAvg = (smCount > 0) ? smSum / smCount : 0;

   // --- Same quarter (full 3-month quarters only) ---
   int    sqCount = 0, sqUp = 0, sqDn = 0;
   double sqSum = 0, sqBest = -DBL_MAX, sqWorst = DBL_MAX;
   int    sqBestYear = 0, sqWorstYear = 0;
   int    firstMonthOfQ = (currentQuarter - 1) * 3 + 1;
   int    lastMonthOfQ  = firstMonthOfQ + 2;

   for(int i = copied - 1; i >= 3; i--) // need i, i-1, i-2 all to be closed bars
   {
      MqlDateTime dt;
      TimeToStruct(rates[i].time, dt);
      if(dt.mon != firstMonthOfQ) continue; // must be the start of the target quarter

      // Verify the next two bars are the 2nd and 3rd months of the same quarter/year
      MqlDateTime dt1, dt2;
      TimeToStruct(rates[i-1].time, dt1);
      TimeToStruct(rates[i-2].time, dt2);
      if(dt1.year != dt.year || dt1.mon != firstMonthOfQ + 1) continue;
      if(dt2.year != dt.year || dt2.mon != lastMonthOfQ)       continue;

      double qOpen  = rates[i].open;
      double qClose = rates[i-2].close;
      double pct    = (qOpen > 0) ? ((qClose - qOpen) / qOpen) * 100.0 : 0;
      sqCount++;
      sqSum += pct;
      if(pct >= 0) sqUp++; else sqDn++;
      if(pct > sqBest)  { sqBest  = pct; sqBestYear  = dt.year; }
      if(pct < sqWorst) { sqWorst = pct; sqWorstYear = dt.year; }
   }
   double sqAvg = (sqCount > 0) ? sqSum / sqCount : 0;

   //===================================================================
   // NARRATIVE ENGINE v2
   // Rule: market state first. One idea per section. Numbers as footnotes.
   //===================================================================

   // PRICE HISTORY — where are we in the long-term cycle?
   string s_history;
   if(athBar == 0)
      s_history = StringFormat(
         "**%s** is currently at the highest price ever recorded in this dataset (**%s**), up **%.1f%%** from its starting point of **%s** on %s.",
         _Symbol, DoubleToString(currentClose, _Digits), netMovePct,
         DoubleToString(firstOpen, _Digits), TimeToString(firstTime, TIME_DATE));
   else if(pctFromATH < 5.0)
      s_history = StringFormat(
         "**%s** is near the top of its recorded history, having pulled back just **%.1f%%** from the all-time high of **%s** reached **%d months ago**.",
         _Symbol, pctFromATH, DoubleToString(allTimeHigh, _Digits), athBar);
   else if(pctFromATH < 25.0)
      s_history = StringFormat(
         "**%s** is in a corrective phase from its historical peak of **%s** — currently **%.1f%%** below that level, reached **%d months ago**.",
         _Symbol, DoubleToString(allTimeHigh, _Digits), pctFromATH, athBar);
   else
      s_history = StringFormat(
         "**%s** is well below its historical peak — **%.1f%%** from the all-time high of **%s** set **%d months ago**, and **%.1f%%** above the all-time low of **%s**.",
         _Symbol, pctFromATH, DoubleToString(allTimeHigh, _Digits), athBar,
         pctFromATL, DoubleToString(allTimeLow, _Digits));

   // MARKET STRUCTURE — is momentum expanding, fading, or reversing?
   string s_structure;
   bool hhOn = (consHH > 0), hlOn = (consHL > 0), lhOn = (consLH > 0), llOn = (consLL > 0);
   if(hhOn && hlOn)
      s_structure = StringFormat(
         "Momentum is intact — **%d** consecutive months have produced both higher highs and higher lows, with the full range climbing. The most recent **%d months** have also printed higher peaks and higher troughs than the **%d months** before them.",
         MathMin(consHH, consHL), half, half);
   else if(lhOn && llOn)
      s_structure = StringFormat(
         "Momentum is fading — **%d** consecutive months have produced both lower highs and lower lows, with the full range declining. The most recent **%d months** have also printed lower peaks and lower troughs than the **%d months** before them.",
         MathMin(consLH, consLL), half, half);
   else if(hhOn && llOn)
      s_structure = StringFormat(
         "The range is expanding in both directions — **%d** consecutive months of higher highs alongside **%d** months of lower lows, with no directional commitment from the market.",
         consHH, consLL);
   else if(lhOn && hlOn)
      s_structure = StringFormat(
         "The range is contracting — falling highs for **%d** months while lows have been rising for **%d** months, compressing price into a narrowing corridor.",
         consLH, consHL);
   else if(hhOn)
      s_structure = StringFormat(
         "Peaks have been climbing for **%d** consecutive months, though the lows have not confirmed a consistent direction alongside them.",
         consHH);
   else if(lhOn)
      s_structure = StringFormat(
         "Peaks have been declining for **%d** consecutive months, though the lows have not confirmed a consistent direction.",
         consLH);
   else if(hlOn)
      s_structure = StringFormat(
         "The floor has been rising for **%d** consecutive months, though the highs have not established a consistent pattern.",
         consHL);
   else if(llOn)
      s_structure = StringFormat(
         "The floor has been declining for **%d** consecutive months, though the highs have not established a consistent pattern.",
         consLL);
   else
      s_structure = "No consecutive sequence in either highs or lows — structure is currently mixed with no clear directional pattern.";

   // RANGE POSITION — where are we in equilibrium?
   string s_range;
   if(pctIn24mRange >= 80)
      s_range = StringFormat(
         "Price is pressing against the upper end of its two-year range, sitting just **%.1f%%** from the two-year high of **%s** set **%d months ago**.",
         distFromHigh24m, DoubleToString(high24m, _Digits), high24mBar);
   else if(pctIn24mRange >= 60)
      s_range = StringFormat(
         "Price is in the upper portion of its two-year range (**%s** to **%s**), **%.1f%%** from the recent high.",
         DoubleToString(low24m, _Digits), DoubleToString(high24m, _Digits), distFromHigh24m);
   else if(pctIn24mRange >= 40)
      s_range = StringFormat(
         "Price is near the midpoint of its two-year range (**%s** to **%s**), **%.1f%%** from each extreme.",
         DoubleToString(low24m, _Digits), DoubleToString(high24m, _Digits),
         MathMin(distFromHigh24m, distFromLow24m));
   else if(pctIn24mRange >= 20)
      s_range = StringFormat(
         "Price is in the lower portion of its two-year range (**%s** to **%s**), **%.1f%%** from the recent low.",
         DoubleToString(low24m, _Digits), DoubleToString(high24m, _Digits), distFromLow24m);
   else
      s_range = StringFormat(
         "Price is pressing against the lower end of its two-year range, sitting just **%.1f%%** from the two-year low of **%s** set **%d months ago**.",
         distFromLow24m, DoubleToString(low24m, _Digits), low24mBar);

   // PERCENTILE — how unusual is this price in the full history?
   string s_percentile;
   if(percentile >= 97)
      s_percentile = StringFormat(
         "This price level is at the extreme upper end of the full historical record — above **%.0f%%** of all monthly closes ever recorded in this dataset.",
         percentile);
   else if(percentile >= 85)
      s_percentile = StringFormat(
         "Price remains historically elevated — higher than **%.0f%%** of all monthly closes across **%d months** of data.",
         percentile, totalCloses);
   else if(percentile >= 60)
      s_percentile = StringFormat(
         "Price sits in the upper portion of its long-term historical distribution (**%.0fth percentile** of **%d** recorded closes).",
         percentile, totalCloses);
   else if(percentile >= 40)
      s_percentile = StringFormat(
         "Price is near the long-term median of all historical closes (**%.0fth percentile** of **%d** recorded months).",
         percentile, totalCloses);
   else if(percentile >= 15)
      s_percentile = StringFormat(
         "Price sits in the lower portion of its long-term historical distribution (**%.0fth percentile** of **%d** recorded closes).",
         percentile, totalCloses);
   else if(percentile >= 3)
      s_percentile = StringFormat(
         "Price is historically depressed — below **%.0f%%** of all monthly closes across **%d months** of data.",
         100.0 - percentile, totalCloses);
   else
      s_percentile = StringFormat(
         "This price level is at the extreme lower end of the full historical record — below **%.0f%%** of all monthly closes ever recorded.",
         100.0 - percentile);

   // DRAWDOWN — how stressed is the trend?
   string s_dd, s_ddContext = "";
   if(pctFromATH < 1.0)
      s_dd = "There is no current drawdown — price is at its all-time high.";
   else if(ddCount == 0 || pctFromATH < avgDD * 0.6)
      s_dd = StringFormat(
         "The current pullback of **%.1f%%** is shallow relative to this market's history. *(Average correction on record: **%.1f%%**, deepest: **%.1f%%.)*",
         pctFromATH, avgDD, largestDD);
   else if(pctFromATH < avgDD)
      s_dd = StringFormat(
         "The current pullback of **%.1f%%** is approaching, but has not yet reached, the typical correction depth of **%.1f%%** recorded in this dataset.",
         pctFromATH, avgDD);
   else if(pctFromATH < largestDD)
      s_dd = StringFormat(
         "The current pullback of **%.1f%%** has exceeded the average correction depth (**%.1f%%**) but remains below the deepest drawdown on record (**%.1f%%**).",
         pctFromATH, avgDD, largestDD);
   else
      s_dd = StringFormat(
         "The current pullback of **%.1f%%** matches or exceeds the deepest correction ever recorded in this dataset (**%.1f%%**).",
         pctFromATH, largestDD);
   if(pctFromATH > 1.0)
      s_ddContext = (deepDDCount > 0)
         ? StringFormat("A pullback of this depth has occurred **%d** time(s) in **%.0f years** of history.", deepDDCount, copied / 12.0)
         : StringFormat("No previous correction in **%.0f years** of history has reached this depth.", copied / 12.0);

   // VOLATILITY — is the market in a calm or active regime?
   string s_vol;
   if(volPercentile >= 90)
      s_vol = StringFormat(
         "The market is in a high-activity regime — monthly swings over the past year are larger than at nearly any point in this dataset's history. *(Volatility: **%.2f%%** vs average **%.2f%%.)*",
         currentVol, historicalVol);
   else if(volPercentile >= 65)
      s_vol = StringFormat(
         "Activity is running above the historical norm — monthly swings have been wider than usual. *(Volatility: **%.2f%%** vs average **%.2f%%.)*",
         currentVol, historicalVol);
   else if(volPercentile <= 10)
      s_vol = StringFormat(
         "The market is in an unusually quiet regime — monthly swings are narrower than at nearly any point in this dataset's history. *(Volatility: **%.2f%%** vs average **%.2f%%.)*",
         currentVol, historicalVol);
   else if(volPercentile <= 35)
      s_vol = StringFormat(
         "Activity is running below the historical norm — monthly swings have been calmer than usual. *(Volatility: **%.2f%%** vs average **%.2f%%.)*",
         currentVol, historicalVol);
   else
      s_vol = StringFormat(
         "Activity is running close to the historical norm — monthly swings are typical for this market. *(Volatility: **%.2f%%** vs average **%.2f%%.)*",
         currentVol, historicalVol);

   // MOVING AVERAGES — is price extended from its own recent reference levels?
   string s_ma;
   if(vsShortMA > 0 && vsLongMA > 0)
      s_ma = StringFormat(
         "Price is above both its **%d-month** (**%s**) and **%d-month** (**%s**) averages.",
         InpShortMAPeriod, DoubleToString(shortMA, _Digits),
         InpLongMAPeriod,  DoubleToString(longMA,  _Digits));
   else if(vsShortMA < 0 && vsLongMA < 0)
      s_ma = StringFormat(
         "Price has fallen below both its **%d-month** (**%s**) and **%d-month** (**%s**) averages.",
         InpShortMAPeriod, DoubleToString(shortMA, _Digits),
         InpLongMAPeriod,  DoubleToString(longMA,  _Digits));
   else if(vsShortMA > 0 && vsLongMA < 0)
      s_ma = StringFormat(
         "Price is above the **%d-month** average (**%s**) but has not recovered past the **%d-month** average (**%s**).",
         InpShortMAPeriod, DoubleToString(shortMA, _Digits),
         InpLongMAPeriod,  DoubleToString(longMA,  _Digits));
   else
      s_ma = StringFormat(
         "Price has slipped below the **%d-month** average (**%s**) while remaining above the **%d-month** average (**%s**).",
         InpShortMAPeriod, DoubleToString(shortMA, _Digits),
         InpLongMAPeriod,  DoubleToString(longMA,  _Digits));

   // CANDLE SEQUENCE — what has the recent monthly rhythm looked like?
   string s_seq;
   if(upCount > downCount + 1)
      s_seq = StringFormat(
         "Recent months have leaned toward up-closes — **%d** up and **%d** down across the last **%d** completed candles: **%s**.",
         upCount, downCount, recentWindow, recentBars);
   else if(downCount > upCount + 1)
      s_seq = StringFormat(
         "Recent months have leaned toward down-closes — **%d** down and **%d** up across the last **%d** completed candles: **%s**.",
         downCount, upCount, recentWindow, recentBars);
   else
      s_seq = StringFormat(
         "Recent months have been evenly split with no directional lean across **%d** completed candles: **%s**.",
         recentWindow, recentBars);

   // SEASONAL — is time adding bias or noise?
   string s_month, s_quarter;
   if(smCount < 5)
      s_month = StringFormat(
         "Only **%d** recorded instances of **%s** in this dataset — not enough to identify seasonal patterns.",
         smCount, MonthName(currentMonth));
   else
   {
      double smUpRate = (double)smUp / smCount * 100.0;
      if(smUpRate > 65)
         s_month = StringFormat(
            "**%s** has historically leaned toward up-closes (**%d** of **%d** instances, **%.0f%%**), with an average recorded change of **%.2f%%** and a range from **+%.2f%%** (%d) to **%.2f%%** (%d).",
            MonthName(currentMonth), smUp, smCount, smUpRate, smAvg, smBest, smBestYear, smWorst, smWorstYear);
      else if(smUpRate < 35)
         s_month = StringFormat(
            "**%s** has historically leaned toward down-closes (**%d** of **%d** instances, **%.0f%%**), with an average recorded change of **%.2f%%** and a range from **+%.2f%%** (%d) to **%.2f%%** (%d).",
            MonthName(currentMonth), smDn, smCount, 100.0-smUpRate, smAvg, smBest, smBestYear, smWorst, smWorstYear);
      else
         s_month = StringFormat(
            "**%s** shows no consistent directional tendency across **%d** instances — roughly balanced between up and down, averaging **%.2f%%**, ranging from **+%.2f%%** (%d) to **%.2f%%** (%d).",
            MonthName(currentMonth), smCount, smAvg, smBest, smBestYear, smWorst, smWorstYear);
   }
   if(sqCount < 4)
      s_quarter = StringFormat(
         "Only **%d** complete instances of **%s** — not enough to identify seasonal patterns.",
         sqCount, QuarterLabel(currentQuarter));
   else
   {
      double sqUpRate = (double)sqUp / sqCount * 100.0;
      if(sqUpRate > 65)
         s_quarter = StringFormat(
            "**%s** has historically leaned toward positive closes (**%d** of **%d** instances, **%.0f%%**), averaging **%.2f%%**, ranging from **+%.2f%%** (%d) to **%.2f%%** (%d).",
            QuarterLabel(currentQuarter), sqUp, sqCount, sqUpRate, sqAvg, sqBest, sqBestYear, sqWorst, sqWorstYear);
      else if(sqUpRate < 35)
         s_quarter = StringFormat(
            "**%s** has historically leaned toward negative closes (**%d** of **%d** instances, **%.0f%%**), averaging **%.2f%%**, ranging from **+%.2f%%** (%d) to **%.2f%%** (%d).",
            QuarterLabel(currentQuarter), sqDn, sqCount, 100.0-sqUpRate, sqAvg, sqBest, sqBestYear, sqWorst, sqWorstYear);
      else
         s_quarter = StringFormat(
            "**%s** shows no consistent directional tendency across **%d** instances — roughly balanced, averaging **%.2f%%**, ranging from **+%.2f%%** (%d) to **%.2f%%** (%d).",
            QuarterLabel(currentQuarter), sqCount, sqAvg, sqBest, sqBestYear, sqWorst, sqWorstYear);
   }

   //===================================================================
   // PRINT
   //===================================================================
   Print("# Market Intelligence Report — " + _Symbol + " — " + TimeToString(TimeCurrent(), TIME_DATE));
   Print("");

   Print("## Price History");
   Print("");
   Print(s_history);
   Print("");

   Print("## Market Structure");
   Print("");
   Print(s_structure);
   Print("");

   Print("## Range Position");
   Print("");
   Print(s_range);
   Print("");

   Print("## Percentile Ranking");
   Print("");
   Print(s_percentile);
   Print("");

   Print("## Drawdown");
   Print("");
   Print(s_dd);
   if(s_ddContext != "") Print(s_ddContext);
   Print("");

   Print("## Volatility");
   Print("");
   Print(s_vol);
   Print("");

   Print("## Moving Averages");
   Print("");
   Print(s_ma);
   Print("");

   Print("## Candle Behaviour");
   Print("");
   Print(s_seq);
   Print("");
   Print("**Last completed candle:**");
   double prevPct = (prevOpen > 0) ? ((prevClose - prevOpen) / prevOpen) * 100.0 : 0.0;
   Print(StringFormat("Opened at **%s**, closed at **%s** (**%.2f%%**).",
      DoubleToString(prevOpen, _Digits), DoubleToString(prevClose, _Digits), prevPct));
   Print("");
   Print("**Current candle (still forming):**");
   Print(StringFormat(
      "Opened at **%s**. High: **%s**. Low: **%s**. Currently at **%s** (**%.2f%%** from last month's close). Body: **%.0f%%** of total range. Upper wick: **%s**. Lower wick: **%s**.",
      DoubleToString(currentOpen,  _Digits), DoubleToString(currentHigh, _Digits),
      DoubleToString(currentLow,   _Digits), DoubleToString(currentClose, _Digits),
      moveFromPrevPct, bodyPct,
      DoubleToString(upperWick, _Digits), DoubleToString(lowerWick, _Digits)));
   Print("");

   Print("## Seasonal Statistics");
   Print("");
   Print(s_month);
   Print("");
   Print(s_quarter);
   Print("");

   Print("---");
   Print("");
   Print(StringFormat(
      "*Dataset: **%d monthly candles**, **%.1f years** (%s – %s). Closes: **%d**. Corrections: **%d**. Vol windows: **%d**. %s instances: **%d**. %s instances: **%d**.*",
      copied, copied / 12.0,
      TimeToString(firstTime, TIME_DATE), TimeToString(TimeCurrent(), TIME_DATE),
      totalCloses, ddCount, MathMax(0, retCount - 11),
      MonthName(currentMonth), smCount, QuarterLabel(currentQuarter), sqCount));
   Print("");
}
//+------------------------------------------------------------------+
