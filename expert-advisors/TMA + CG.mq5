//+------------------------------------------------------------------+
//|                                          TMA+CG mladen alerts.mq5|
//|                                                           mladen |
//| arrows coded according to idea presented by rajiv                |
//+------------------------------------------------------------------+
#property copyright "rajivxxx"
#property link      "rajivxxx@gmail.com"

#property description "edited by eevviill"
#property description "no repaint - MT5 version"

#property indicator_chart_window
#property indicator_buffers 19
#property indicator_plots   17

#property indicator_label1   "TMA Middle"
#property indicator_type1    DRAW_LINE
#property indicator_color1   clrDimGray
#property indicator_style1   STYLE_DOT
#property indicator_width1   1

#property indicator_label2   "TMA Upper 1"
#property indicator_type2    DRAW_LINE
#property indicator_color2   clrHotPink
#property indicator_style2   STYLE_DOT
#property indicator_width2   1

#property indicator_label3   "TMA Lower 1"
#property indicator_type3    DRAW_LINE
#property indicator_color3   clrSpringGreen
#property indicator_style3   STYLE_DOT
#property indicator_width3   1

#property indicator_label4   "TMA Upper 2"
#property indicator_type4    DRAW_LINE
#property indicator_color4   clrHotPink
#property indicator_style4   STYLE_DOT
#property indicator_width4   1

#property indicator_label5   "TMA Lower 2"
#property indicator_type5    DRAW_LINE
#property indicator_color5   clrSpringGreen
#property indicator_style5   STYLE_DOT
#property indicator_width5   1

#property indicator_label6   "TMA Upper 3"
#property indicator_type6    DRAW_LINE
#property indicator_color6   clrHotPink
#property indicator_style6   STYLE_DOT
#property indicator_width6   1

#property indicator_label7   "TMA Lower 3"
#property indicator_type7    DRAW_LINE
#property indicator_color7   clrSpringGreen
#property indicator_style7   STYLE_DOT
#property indicator_width7   1

#property indicator_label8   "TMA Upper 4"
#property indicator_type8    DRAW_LINE
#property indicator_color8   clrHotPink
#property indicator_style8   STYLE_DOT
#property indicator_width8   1

#property indicator_label9   "TMA Lower 4"
#property indicator_type9    DRAW_LINE
#property indicator_color9   clrSpringGreen
#property indicator_style9   STYLE_DOT
#property indicator_width9   1

#property indicator_label10  "TMA Upper 5"
#property indicator_type10   DRAW_LINE
#property indicator_color10  clrHotPink
#property indicator_style10  STYLE_DOT
#property indicator_width10  1

#property indicator_label11  "TMA Lower 5"
#property indicator_type11   DRAW_LINE
#property indicator_color11  clrSpringGreen
#property indicator_style11  STYLE_DOT
#property indicator_width11  1

#property indicator_label12  "TMA Upper 6"
#property indicator_type12   DRAW_LINE
#property indicator_color12  clrHotPink
#property indicator_style12  STYLE_SOLID
#property indicator_width12  1

#property indicator_label13  "TMA Lower 6"
#property indicator_type13   DRAW_LINE
#property indicator_color13  clrSpringGreen
#property indicator_style13  STYLE_SOLID
#property indicator_width13  1

#property indicator_label14  "TMA Upper 7"
#property indicator_type14   DRAW_LINE
#property indicator_color14  clrHotPink
#property indicator_style14  STYLE_SOLID
#property indicator_width14  2

#property indicator_label15  "TMA Lower 7"
#property indicator_type15   DRAW_LINE
#property indicator_color15  clrSpringGreen
#property indicator_style15  STYLE_SOLID
#property indicator_width15  2

#property indicator_label16  "Up Arrow"
#property indicator_type16   DRAW_ARROW
#property indicator_color16  clrRed
#property indicator_width16  1

#property indicator_label17  "Down Arrow"
#property indicator_type17   DRAW_ARROW
#property indicator_color17  clrLime
#property indicator_width17  1

//--- input parameters
input string   TimeFrame           = "current time frame";
input int      HalfLength          = 56;
input ENUM_APPLIED_PRICE AppliedPrice = PRICE_WEIGHTED;
input double   BandsDeviations1    = 1.618;
input double   BandsDeviations2    = 2.0;
input double   BandsDeviations3    = 2.236;
input double   BandsDeviations4    = 2.5;
input double   BandsDeviations5    = 2.618;
input double   BandsDeviations6    = 3.0;
input double   BandsDeviations7    = 3.236;
input bool     DrawArrows          = true;
input int      UpArrowSymbolCode   = 246;
input int      DownArrowSymbolCode = 248;
input bool     alertsOn            = false;
input bool     alertsOnCurrent     = false;
input bool     alertsOnHighLow     = false;
input bool     Interpolate         = true;

bool   alertsMessage   = false;
bool   alertsSound     = false;
bool   alertsEmail     = false;

//--- indicator buffers
double tmBuffer[], upBuffer1[], dnBuffer1[], upBuffer2[], dnBuffer2[], upBuffer3[], dnBuffer3[]; 
double upBuffer4[], dnBuffer4[], upBuffer5[], dnBuffer5[], upBuffer6[], dnBuffer6[], upBuffer7[], dnBuffer7[];
double upArrow[], dnArrow[];
double wuBuffer[], wdBuffer[];

string IndicatorFileName;
bool   calculatingTma = false;
bool   returningBars  = false;
ENUM_TIMEFRAMES timeFrame;
int    maPeriod1Handle;
int    atrHandle;

//+------------------------------------------------------------------+
//| Custom indicator initialization function                         |
//+------------------------------------------------------------------+
int OnInit()
{
   timeFrame = StringToTimeFrame(TimeFrame);
   
   IndicatorSetString(INDICATOR_SHORTNAME, "TMA+CG(" + EnumToString(timeFrame) + ")");
   
   //--- indicator buffers mapping
   SetIndexBuffer(0, tmBuffer, INDICATOR_DATA);
   SetIndexBuffer(1, upBuffer1, INDICATOR_DATA);
   SetIndexBuffer(2, dnBuffer1, INDICATOR_DATA);
   SetIndexBuffer(3, upBuffer2, INDICATOR_DATA);
   SetIndexBuffer(4, dnBuffer2, INDICATOR_DATA);
   SetIndexBuffer(5, upBuffer3, INDICATOR_DATA);
   SetIndexBuffer(6, dnBuffer3, INDICATOR_DATA);
   SetIndexBuffer(7, upBuffer4, INDICATOR_DATA);
   SetIndexBuffer(8, dnBuffer4, INDICATOR_DATA);
   SetIndexBuffer(9, upBuffer5, INDICATOR_DATA);
   SetIndexBuffer(10, dnBuffer5, INDICATOR_DATA);
   SetIndexBuffer(11, upBuffer6, INDICATOR_DATA);
   SetIndexBuffer(12, dnBuffer6, INDICATOR_DATA);
   SetIndexBuffer(13, upBuffer7, INDICATOR_DATA);
   SetIndexBuffer(14, dnBuffer7, INDICATOR_DATA);
   
   //--- Set buffers as series
   ArraySetAsSeries(tmBuffer, true);
   ArraySetAsSeries(upBuffer1, true);
   ArraySetAsSeries(dnBuffer1, true);
   ArraySetAsSeries(upBuffer2, true);
   ArraySetAsSeries(dnBuffer2, true);
   ArraySetAsSeries(upBuffer3, true);
   ArraySetAsSeries(dnBuffer3, true);
   ArraySetAsSeries(upBuffer4, true);
   ArraySetAsSeries(dnBuffer4, true);
   ArraySetAsSeries(upBuffer5, true);
   ArraySetAsSeries(dnBuffer5, true);
   ArraySetAsSeries(upBuffer6, true);
   ArraySetAsSeries(dnBuffer6, true);
   ArraySetAsSeries(upBuffer7, true);
   ArraySetAsSeries(dnBuffer7, true);
   
   if(DrawArrows)
   {
      SetIndexBuffer(15, upArrow, INDICATOR_DATA);
      PlotIndexSetInteger(15, PLOT_ARROW, DownArrowSymbolCode);
      PlotIndexSetInteger(15, PLOT_DRAW_TYPE, DRAW_ARROW);
      
      SetIndexBuffer(16, dnArrow, INDICATOR_DATA);
      PlotIndexSetInteger(16, PLOT_ARROW, UpArrowSymbolCode);
      PlotIndexSetInteger(16, PLOT_DRAW_TYPE, DRAW_ARROW);
   }
   else
   {
      SetIndexBuffer(15, upArrow, INDICATOR_CALCULATIONS);
      SetIndexBuffer(16, dnArrow, INDICATOR_CALCULATIONS);
   }
   
   SetIndexBuffer(17, wuBuffer, INDICATOR_CALCULATIONS);
   SetIndexBuffer(18, wdBuffer, INDICATOR_CALCULATIONS);
   
   ArraySetAsSeries(upArrow, true);
   ArraySetAsSeries(dnArrow, true);
   ArraySetAsSeries(wuBuffer, true);
   ArraySetAsSeries(wdBuffer, true);
   
   //--- set drawing begin
   PlotIndexSetInteger(0, PLOT_DRAW_BEGIN, HalfLength);
   for(int i = 1; i < 17; i++)
      PlotIndexSetInteger(i, PLOT_DRAW_BEGIN, HalfLength);
   
   //--- create handle for MA
   atrHandle = iATR(_Symbol, PERIOD_CURRENT, 20);
   
   if(TimeFrame == "calculateTma")
   {
      calculatingTma = true;
      return(INIT_SUCCEEDED);
   }
   if(TimeFrame == "returnBars")
   {
      returningBars = true;
      return(INIT_SUCCEEDED);
   }
   
   IndicatorFileName = MQLInfoString(MQL_PROGRAM_NAME);
   
   return(INIT_SUCCEEDED);
}

//+------------------------------------------------------------------+
//| Custom indicator deinitialization function                       |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
   if(atrHandle != INVALID_HANDLE)
      IndicatorRelease(atrHandle);
}

//+------------------------------------------------------------------+
//| Custom indicator iteration function                              |
//+------------------------------------------------------------------+
int OnCalculate(const int rates_total,
                const int prev_calculated,
                const datetime &time[],
                const double &open[],
                const double &high[],
                const double &low[],
                const double &close[],
                const long &tick_volume[],
                const long &volume[],
                const int &spread[])
{
   ArraySetAsSeries(time, true);
   ArraySetAsSeries(open, true);
   ArraySetAsSeries(high, true);
   ArraySetAsSeries(low, true);
   ArraySetAsSeries(close, true);
   
   int counted_bars = prev_calculated;
   int limit;
   
   if(counted_bars < 0) return(-1);
   if(counted_bars > 0) counted_bars--;
   
   limit = rates_total - counted_bars - 1;
   if(limit > rates_total - HalfLength - 1)
      limit = rates_total - HalfLength - 1;
   
   if(returningBars)
   {
      tmBuffer[0] = limit;
      return(rates_total);
   }
   
   if(calculatingTma)
   {
      CalculateTma(rates_total, limit, open, high, low, close, time);
      return(rates_total);
   }
   
   //--- Main calculation loop
   for(int i = limit; i >= 0; i--)
   {
      //--- Calculate TMA directly
      CalculateTmaAtBar(i, rates_total, open, high, low, close, time);
      
      //--- Draw arrows
      if(DrawArrows && i < rates_total - 1 && i > 0)
      {
         upArrow[i] = EMPTY_VALUE;
         dnArrow[i] = EMPTY_VALUE;
         
         double atrValue[];
         ArraySetAsSeries(atrValue, true);
         if(CopyBuffer(atrHandle, 0, i, 1, atrValue) > 0)
         {
            if(high[i+1] > upBuffer1[i+1] && close[i+1] > open[i+1] && close[i] < open[i])
               upArrow[i] = high[i] + atrValue[0];
            if(low[i+1] < dnBuffer1[i+1] && close[i+1] < open[i+1] && close[i] > open[i])
               dnArrow[i] = low[i] - atrValue[0];
         }
      }
   }
   
   //--- Alerts
   if(alertsOn)
   {
      int forBar = alertsOnCurrent ? 0 : 1;
      
      if(alertsOnHighLow)
      {
         if(high[forBar] > upBuffer1[forBar] && high[forBar+1] < upBuffer1[forBar+1])
            DoAlert("high penetrated upper bar");
         if(low[forBar] < dnBuffer1[forBar] && low[forBar+1] > dnBuffer1[forBar+1])
            DoAlert("low penetrated lower bar");
      }
      else
      {
         if(close[forBar] > upBuffer1[forBar] && close[forBar+1] < upBuffer1[forBar+1])
            DoAlert("close penetrated upper bar");
         if(close[forBar] < dnBuffer1[forBar] && close[forBar+1] > dnBuffer1[forBar+1])
            DoAlert("close penetrated lower bar");
      }
   }
   
   return(rates_total);
}

//+------------------------------------------------------------------+
//| Calculate TMA for all bars                                       |
//+------------------------------------------------------------------+
void CalculateTma(int rates_total, int limit, const double &open[], const double &high[], 
                  const double &low[], const double &close[], const datetime &time[])
{
   double FullLength = 2.0 * HalfLength + 1.0;
   
   for(int i = limit; i >= 0; i--)
   {
      CalculateTmaAtBar(i, rates_total, open, high, low, close, time);
   }
}

//+------------------------------------------------------------------+
//| Calculate TMA at specific bar                                    |
//+------------------------------------------------------------------+
void CalculateTmaAtBar(int i, int rates_total, const double &open[], const double &high[],
                       const double &low[], const double &close[], const datetime &time[])
{
   double FullLength = 2.0 * HalfLength + 1.0;
   
   //--- Calculate TMA (Triangular Moving Average)
   double sum = (HalfLength + 1) * GetPrice(AppliedPrice, i, open, high, low, close, rates_total);
   double sumw = (HalfLength + 1);
   
   for(int j = 1, k = HalfLength; j <= HalfLength; j++, k--)
   {
      sum += k * GetPrice(AppliedPrice, i + j, open, high, low, close, rates_total);
      sumw += k;
      
      if(j <= i)
      {
         sum += k * GetPrice(AppliedPrice, i - j, open, high, low, close, rates_total);
         sumw += k;
      }
   }
   
   tmBuffer[i] = sum / sumw;
   
   //--- Calculate bands based on deviation
   double diff = GetPrice(AppliedPrice, i, open, high, low, close, rates_total) - tmBuffer[i];
   
   if(i >= (rates_total - HalfLength - 1))
   {
      if(i == (rates_total - HalfLength - 1))
      {
         upBuffer1[i] = tmBuffer[i];
         dnBuffer1[i] = tmBuffer[i];
         upBuffer2[i] = tmBuffer[i];
         dnBuffer2[i] = tmBuffer[i];
         upBuffer3[i] = tmBuffer[i];
         dnBuffer3[i] = tmBuffer[i];
         upBuffer4[i] = tmBuffer[i];
         dnBuffer4[i] = tmBuffer[i];
         upBuffer5[i] = tmBuffer[i];
         dnBuffer5[i] = tmBuffer[i];
         upBuffer6[i] = tmBuffer[i];
         dnBuffer6[i] = tmBuffer[i];
         upBuffer7[i] = tmBuffer[i];
         dnBuffer7[i] = tmBuffer[i];
         
         if(diff >= 0)
         {
            wuBuffer[i] = MathPow(diff, 2);
            wdBuffer[i] = 0;
         }
         else
         {
            wdBuffer[i] = MathPow(diff, 2);
            wuBuffer[i] = 0;
         }
      }
      return;
   }
   
   if(diff >= 0)
   {
      wuBuffer[i] = (wuBuffer[i+1] * (FullLength - 1) + MathPow(diff, 2)) / FullLength;
      wdBuffer[i] = wdBuffer[i+1] * (FullLength - 1) / FullLength;
   }
   else
   {
      wdBuffer[i] = (wdBuffer[i+1] * (FullLength - 1) + MathPow(diff, 2)) / FullLength;
      wuBuffer[i] = wuBuffer[i+1] * (FullLength - 1) / FullLength;
   }
   
   upBuffer1[i] = tmBuffer[i] + BandsDeviations1 * MathSqrt(wuBuffer[i]);
   dnBuffer1[i] = tmBuffer[i] - BandsDeviations1 * MathSqrt(wdBuffer[i]);
   upBuffer2[i] = tmBuffer[i] + BandsDeviations2 * MathSqrt(wuBuffer[i]);
   dnBuffer2[i] = tmBuffer[i] - BandsDeviations2 * MathSqrt(wdBuffer[i]);
   upBuffer3[i] = tmBuffer[i] + BandsDeviations3 * MathSqrt(wuBuffer[i]);
   dnBuffer3[i] = tmBuffer[i] - BandsDeviations3 * MathSqrt(wdBuffer[i]);
   upBuffer4[i] = tmBuffer[i] + BandsDeviations4 * MathSqrt(wuBuffer[i]);
   dnBuffer4[i] = tmBuffer[i] - BandsDeviations4 * MathSqrt(wdBuffer[i]);
   upBuffer5[i] = tmBuffer[i] + BandsDeviations5 * MathSqrt(wuBuffer[i]);
   dnBuffer5[i] = tmBuffer[i] - BandsDeviations5 * MathSqrt(wdBuffer[i]);
   upBuffer6[i] = tmBuffer[i] + BandsDeviations6 * MathSqrt(wuBuffer[i]);
   dnBuffer6[i] = tmBuffer[i] - BandsDeviations6 * MathSqrt(wdBuffer[i]);
   upBuffer7[i] = tmBuffer[i] + BandsDeviations7 * MathSqrt(wuBuffer[i]);
   dnBuffer7[i] = tmBuffer[i] - BandsDeviations7 * MathSqrt(wdBuffer[i]);
}

//+------------------------------------------------------------------+
//| Get price based on applied price type                            |
//+------------------------------------------------------------------+
double GetPrice(ENUM_APPLIED_PRICE price, int shift, const double &open[], const double &high[],
                const double &low[], const double &close[], int rates_total)
{
   if(shift < 0 || shift >= rates_total)
      return(0.0);
      
   switch(price)
   {
      case PRICE_CLOSE:    return(close[shift]);
      case PRICE_OPEN:     return(open[shift]);
      case PRICE_HIGH:     return(high[shift]);
      case PRICE_LOW:      return(low[shift]);
      case PRICE_MEDIAN:   return((high[shift] + low[shift]) / 2.0);
      case PRICE_TYPICAL:  return((high[shift] + low[shift] + close[shift]) / 3.0);
      case PRICE_WEIGHTED: return((high[shift] + low[shift] + close[shift] + close[shift]) / 4.0);
      default:             return(close[shift]);
   }
}

//+------------------------------------------------------------------+
//| Alert function                                                    |
//+------------------------------------------------------------------+
void DoAlert(string doWhat)
{
   static string previousAlert = "";
   static datetime previousTime;
   
   if(previousAlert != doWhat || previousTime != iTime(_Symbol, PERIOD_CURRENT, 0))
   {
      previousAlert = doWhat;
      previousTime = iTime(_Symbol, PERIOD_CURRENT, 0);
      
      string message = StringFormat("%s at %s TMA: %s", _Symbol, TimeToString(TimeLocal(), TIME_SECONDS), doWhat);
      
      if(alertsMessage)
         Alert(message);
      if(alertsEmail)
         SendMail(_Symbol + " TMA", message);
      if(alertsSound)
         PlaySound("alert2.wav");
   }
}

//+------------------------------------------------------------------+
//| Convert string to timeframe                                       |
//+------------------------------------------------------------------+
ENUM_TIMEFRAMES StringToTimeFrame(string tfs)
{
   StringToUpper(tfs);
   
   ENUM_TIMEFRAMES tf = PERIOD_CURRENT;
   
   if(tfs == "M1" || tfs == "1")        tf = PERIOD_M1;
   else if(tfs == "M5" || tfs == "5")   tf = PERIOD_M5;
   else if(tfs == "M15" || tfs == "15") tf = PERIOD_M15;
   else if(tfs == "M30" || tfs == "30") tf = PERIOD_M30;
   else if(tfs == "H1" || tfs == "60")  tf = PERIOD_H1;
   else if(tfs == "H4" || tfs == "240") tf = PERIOD_H4;
   else if(tfs == "D1" || tfs == "1440") tf = PERIOD_D1;
   else if(tfs == "W1" || tfs == "10080") tf = PERIOD_W1;
   else if(tfs == "MN" || tfs == "MN1" || tfs == "43200") tf = PERIOD_MN1;
   else if(tfs == "CURRENT" || tfs == "0") tf = PERIOD_CURRENT;
   
   if(tf < Period())
      tf = Period();
   
   return(tf);
}

//+------------------------------------------------------------------+
