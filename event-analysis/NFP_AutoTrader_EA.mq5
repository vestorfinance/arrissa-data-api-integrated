//+------------------------------------------------------------------+
//|                                          NFP_AutoTrader_EA.mq5   |
//|                              Based on Enhanced Weighted Strategy |
//|                         Trades 1 minute BEFORE NFP, exits at NFP |
//+------------------------------------------------------------------+
#property copyright "NFP Analysis System"
#property version   "3.00"
#property description "Automated NFP trading with 59% weighted signals"
#property description "Entry: 1 minute BEFORE NFP release"
#property description "Exit: AT NFP release time (1-min candle close)"
#property description "53.06% Win Rate (26W/23L) - 59% System"

#include <Trade\Trade.mqh>

//--- Input parameters
input double LotSize = 0.01;              // Lot size
input int Slippage = 50;                   // Slippage in points
input bool InvertSignals = true;           // Invert signals (BUY becomes SELL)
input string OrderComment = "NFP_AutoTrader"; // Order comment
input int MagicNumber = 123456;            // Magic number

//--- Global variables
CTrade trade;
int totalSignals = 0;
bool tradeExecuted[];
datetime signalTimes[];
string signalDirections[];
double entryPrices[];
double exitPrices[];

//+------------------------------------------------------------------+
//| Expert initialization function                                   |
//+------------------------------------------------------------------+
int OnInit()
{
   Print("NFP AutoTrader EA initialized");
   Print("Lot Size: ", LotSize);
   Print("Trading Symbol: ", _Symbol);
   
   // Setup trade object
   trade.SetExpertMagicNumber(MagicNumber);
   trade.SetDeviationInPoints(Slippage);
   trade.SetTypeFilling(ORDER_FILLING_IOC);
   
   // Initialize all NFP signals (49 total)
   InitializeSignals();
   
   Print("Loaded ", totalSignals, " NFP trading signals");
   Print("Strategy: Entry 1 min BEFORE NFP, Exit AT NFP release");
   Print("Based on 59% Enhanced Weighted System");
   Print("Invert Signals: ", InvertSignals ? "ENABLED (BUY<->SELL)" : "DISABLED");
   
   return(INIT_SUCCEEDED);
}

//+------------------------------------------------------------------+
//| Expert deinitialization function                                 |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
   Print("NFP AutoTrader EA deinitialized. Reason: ", reason);
}

//+------------------------------------------------------------------+
//| Expert tick function                                             |
//+------------------------------------------------------------------+
void OnTick()
{
   datetime currentTime = TimeCurrent();
   
   // Check for signal execution
   for(int i = 0; i < totalSignals; i++)
   {
      datetime entryTime = signalTimes[i] - 60; // 1 minute BEFORE NFP
      datetime exitTime = signalTimes[i];        // AT NFP release time
      
      // Check for entry (only if not already executed)
      if(!tradeExecuted[i])
      {
         // Entry window: within 30 seconds after entry time
         if(currentTime >= entryTime && currentTime < entryTime + 30)
         {
            ExecuteTrade(i);
         }
      }
      
      // Check for exit (if trade was executed)
      if(tradeExecuted[i])
      {
         // Exit window: within 30 seconds after exit time
         if(currentTime >= exitTime && currentTime < exitTime + 30)
         {
            CloseAllPositions();
            return; // Exit after closing positions
         }
      }
   }
}

//+------------------------------------------------------------------+
//| Initialize all NFP trading signals                               |
//+------------------------------------------------------------------+
void InitializeSignals()
{
   totalSignals = 49;
   ArrayResize(tradeExecuted, totalSignals);
   ArrayResize(signalTimes, totalSignals);
   ArrayResize(signalDirections, totalSignals);
   ArrayResize(entryPrices, totalSignals);
   ArrayResize(exitPrices, totalSignals);
   
   // Initialize all to false
   ArrayInitialize(tradeExecuted, false);
   
   // Signal 1: 2026-01-09 - DOWN - WIN
   signalTimes[0] = StringToTime("2026-01-09 13:30:00");
   signalDirections[0] = "DOWN";
   entryPrices[0] = 4472.968;
   exitPrices[0] = 4458.93;

   // Signal 2: 2025-12-16 - DOWN - LOSS
   signalTimes[1] = StringToTime("2025-12-16 13:30:00");
   signalDirections[1] = "DOWN";
   entryPrices[1] = 4301.628;
   exitPrices[1] = 4301.628;

   // Signal 3: 2025-12-16 - DOWN - LOSS
   signalTimes[2] = StringToTime("2025-12-16 13:29:00");
   signalDirections[2] = "DOWN";
   entryPrices[2] = 4300.803;
   exitPrices[2] = 4300.517;

   // Signal 4: 2025-11-20 - DOWN - WIN
   signalTimes[3] = StringToTime("2025-11-20 13:30:00");
   signalDirections[3] = "DOWN";
   entryPrices[3] = 4085.978;
   exitPrices[3] = 4067.177;

   // Signal 5: 2025-09-05 - DOWN - LOSS
   signalTimes[4] = StringToTime("2025-09-05 12:30:00");
   signalDirections[4] = "DOWN";
   entryPrices[4] = 3555.014;
   exitPrices[4] = 3554.884;

   // Signal 6: 2025-08-01 - DOWN - LOSS
   signalTimes[5] = StringToTime("2025-08-01 12:30:00");
   signalDirections[5] = "DOWN";
   entryPrices[5] = 3302.021;
   exitPrices[5] = 3301.927;

   // Signal 7: 2025-07-03 - DOWN - WIN
   signalTimes[6] = StringToTime("2025-07-03 12:30:00");
   signalDirections[6] = "DOWN";
   entryPrices[6] = 3349.745;
   exitPrices[6] = 3330.069;

   // Signal 8: 2025-06-06 - DOWN - WIN
   signalTimes[7] = StringToTime("2025-06-06 12:30:00");
   signalDirections[7] = "DOWN";
   entryPrices[7] = 3359.187;
   exitPrices[7] = 3348.361;

   // Signal 9: 2025-05-02 - DOWN - WIN
   signalTimes[8] = StringToTime("2025-05-02 12:30:00");
   signalDirections[8] = "DOWN";
   entryPrices[8] = 3259.376;
   exitPrices[8] = 3249.883;

   // Signal 10: 2025-04-04 - DOWN - LOSS
   signalTimes[9] = StringToTime("2025-04-04 12:30:00");
   signalDirections[9] = "DOWN";
   entryPrices[9] = 3096.98;
   exitPrices[9] = 3085.43;

   // Signal 11: 2025-03-07 - DOWN - LOSS
   signalTimes[10] = StringToTime("2025-03-07 13:30:00");
   signalDirections[10] = "DOWN";
   entryPrices[10] = 2912.125;
   exitPrices[10] = 2911.654;

   // Signal 12: 2025-02-07 - DOWN - WIN
   signalTimes[11] = StringToTime("2025-02-07 13:30:00");
   signalDirections[11] = "DOWN";
   entryPrices[11] = 2864.464;
   exitPrices[11] = 2860.787;

   // Signal 13: 2025-01-10 - DOWN - WIN
   signalTimes[12] = StringToTime("2025-01-10 13:30:00");
   signalDirections[12] = "DOWN";
   entryPrices[12] = 2677.477;
   exitPrices[12] = 2664.952;

   // Signal 14: 2024-12-06 - DOWN - LOSS
   signalTimes[13] = StringToTime("2024-12-06 13:30:00");
   signalDirections[13] = "DOWN";
   entryPrices[13] = 2635.579;
   exitPrices[13] = 2635.579;

   // Signal 15: 2024-11-01 - DOWN - LOSS
   signalTimes[14] = StringToTime("2024-11-01 12:30:00");
   signalDirections[14] = "DOWN";
   entryPrices[14] = 2750.928;
   exitPrices[14] = 2749.513;

   // Signal 16: 2024-10-04 - DOWN - WIN
   signalTimes[15] = StringToTime("2024-10-04 12:30:00");
   signalDirections[15] = "DOWN";
   entryPrices[15] = 2656.979;
   exitPrices[15] = 2640.332;

   // Signal 17: 2024-09-06 - DOWN - LOSS
   signalTimes[16] = StringToTime("2024-09-06 12:30:00");
   signalDirections[16] = "DOWN";
   entryPrices[16] = 2516.28;
   exitPrices[16] = 2507.481;

   // Signal 18: 2024-08-02 - DOWN - LOSS
   signalTimes[17] = StringToTime("2024-08-02 12:30:00");
   signalDirections[17] = "DOWN";
   entryPrices[17] = 2456.601;
   exitPrices[17] = 2456.601;

   // Signal 19: 2024-07-05 - DOWN - WIN
   signalTimes[18] = StringToTime("2024-07-05 12:30:00");
   signalDirections[18] = "DOWN";
   entryPrices[18] = 2366.678;
   exitPrices[18] = 2347.485;

   // Signal 20: 2024-06-07 - DOWN - WIN
   signalTimes[19] = StringToTime("2024-06-07 12:30:00");
   signalDirections[19] = "DOWN";
   entryPrices[19] = 2333.943;
   exitPrices[19] = 2315.022;

   // Signal 21: 2024-05-03 - DOWN - LOSS
   signalTimes[20] = StringToTime("2024-05-03 12:30:00");
   signalDirections[20] = "DOWN";
   entryPrices[20] = 2296.334;
   exitPrices[20] = 2296.334;

   // Signal 22: 2024-04-05 - DOWN - WIN
   signalTimes[21] = StringToTime("2024-04-05 12:30:00");
   signalDirections[21] = "DOWN";
   entryPrices[21] = 2296.01;
   exitPrices[21] = 2282.504;

   // Signal 23: 2024-03-08 - DOWN - LOSS
   signalTimes[22] = StringToTime("2024-03-08 13:30:00");
   signalDirections[22] = "DOWN";
   entryPrices[22] = 2164.817;
   exitPrices[22] = 2156.49;

   // Signal 24: 2024-02-02 - DOWN - WIN
   signalTimes[23] = StringToTime("2024-02-02 13:30:00");
   signalDirections[23] = "DOWN";
   entryPrices[23] = 2052.627;
   exitPrices[23] = 2041.238;

   // Signal 25: 2024-01-05 - DOWN - WIN
   signalTimes[24] = StringToTime("2024-01-05 13:30:00");
   signalDirections[24] = "DOWN";
   entryPrices[24] = 2039.298;
   exitPrices[24] = 2028.186;

   // Signal 26: 2023-12-08 - DOWN - WIN
   signalTimes[25] = StringToTime("2023-12-08 13:30:00");
   signalDirections[25] = "DOWN";
   entryPrices[25] = 2027.485;
   exitPrices[25] = 2017.104;

   // Signal 27: 2023-11-03 - DOWN - LOSS
   signalTimes[26] = StringToTime("2023-11-03 12:30:00");
   signalDirections[26] = "DOWN";
   entryPrices[26] = 1988.85;
   exitPrices[26] = 1988.703;

   // Signal 28: 2023-10-06 - DOWN - WIN
   signalTimes[27] = StringToTime("2023-10-06 12:30:00");
   signalDirections[27] = "DOWN";
   entryPrices[27] = 1819.671;
   exitPrices[27] = 1811.061;

   // Signal 29: 2023-09-01 - DOWN - LOSS
   signalTimes[28] = StringToTime("2023-09-01 12:30:00");
   signalDirections[28] = "DOWN";
   entryPrices[28] = 1945.646;
   exitPrices[28] = 1945.459;

   // Signal 30: 2023-08-04 - DOWN - LOSS
   signalTimes[29] = StringToTime("2023-08-04 12:30:00");
   signalDirections[29] = "DOWN";
   entryPrices[29] = 1932.188;
   exitPrices[29] = 1925.401;

   // Signal 31: 2023-07-07 - DOWN - WIN
   signalTimes[30] = StringToTime("2023-07-07 12:30:00");
   signalDirections[30] = "DOWN";
   entryPrices[30] = 1921.831;
   exitPrices[30] = 1920.26;

   // Signal 32: 2023-06-02 - DOWN - WIN
   signalTimes[31] = StringToTime("2023-06-02 12:30:00");
   signalDirections[31] = "DOWN";
   entryPrices[31] = 1978.91;
   exitPrices[31] = 1971.715;

   // Signal 33: 2023-05-05 - DOWN - WIN
   signalTimes[32] = StringToTime("2023-05-05 12:30:00");
   signalDirections[32] = "DOWN";
   entryPrices[32] = 2033.796;
   exitPrices[32] = 2020.209;

   // Signal 34: 2023-04-07 - DOWN - LOSS
   signalTimes[33] = StringToTime("2023-04-07 12:30:00");
   signalDirections[33] = "DOWN";
   entryPrices[33] = 4509.312;
   exitPrices[33] = 4509.312;

   // Signal 35: 2023-03-10 - DOWN - LOSS
   signalTimes[34] = StringToTime("2023-03-10 13:30:00");
   signalDirections[34] = "DOWN";
   entryPrices[34] = 1833.701;
   exitPrices[34] = 1833.701;

   // Signal 36: 2023-02-03 - DOWN - WIN
   signalTimes[35] = StringToTime("2023-02-03 13:30:00");
   signalDirections[35] = "DOWN";
   entryPrices[35] = 1913.74;
   exitPrices[35] = 1898.526;

   // Signal 37: 2023-01-06 - DOWN - LOSS
   signalTimes[36] = StringToTime("2023-01-06 13:30:00");
   signalDirections[36] = "DOWN";
   entryPrices[36] = 1835.949;
   exitPrices[36] = 1835.12;

   // Signal 38: 2022-12-02 - DOWN - WIN
   signalTimes[37] = StringToTime("2022-12-02 13:30:00");
   signalDirections[37] = "DOWN";
   entryPrices[37] = 1798.49;
   exitPrices[37] = 1785.394;

   // Signal 39: 2022-11-04 - DOWN - WIN
   signalTimes[38] = StringToTime("2022-11-04 12:30:00");
   signalDirections[38] = "DOWN";
   entryPrices[38] = 1650.256;
   exitPrices[38] = 1641.604;

   // Signal 40: 2022-10-07 - DOWN - WIN
   signalTimes[39] = StringToTime("2022-10-07 12:30:00");
   signalDirections[39] = "DOWN";
   entryPrices[39] = 1710.413;
   exitPrices[39] = 1701.554;

   // Signal 41: 2022-09-02 - DOWN - LOSS
   signalTimes[40] = StringToTime("2022-09-02 12:30:00");
   signalDirections[40] = "DOWN";
   entryPrices[40] = 1705.115;
   exitPrices[40] = 1705.053;

   // Signal 42: 2022-08-05 - DOWN - WIN
   signalTimes[41] = StringToTime("2022-08-05 12:30:00");
   signalDirections[41] = "DOWN";
   entryPrices[41] = 1788.784;
   exitPrices[41] = 1778.378;

   // Signal 43: 2022-07-08 - DOWN - WIN
   signalTimes[42] = StringToTime("2022-07-08 12:30:00");
   signalDirections[42] = "DOWN";
   entryPrices[42] = 1740.942;
   exitPrices[42] = 1733.099;

   // Signal 44: 2022-06-03 - DOWN - LOSS
   signalTimes[43] = StringToTime("2022-06-03 12:30:00");
   signalDirections[43] = "DOWN";
   entryPrices[43] = 1863.576;
   exitPrices[43] = 1860.346;

   // Signal 45: 2022-05-06 - DOWN - LOSS
   signalTimes[44] = StringToTime("2022-05-06 12:30:00");
   signalDirections[44] = "DOWN";
   entryPrices[44] = 1884.345;
   exitPrices[44] = 1884.345;

   // Signal 46: 2022-04-01 - DOWN - WIN
   signalTimes[45] = StringToTime("2022-04-01 12:30:00");
   signalDirections[45] = "DOWN";
   entryPrices[45] = 1930.028;
   exitPrices[45] = 1928.132;

   // Signal 47: 2022-03-04 - DOWN - LOSS
   signalTimes[46] = StringToTime("2022-03-04 13:30:00");
   signalDirections[46] = "DOWN";
   entryPrices[46] = 1944.541;
   exitPrices[46] = 1943.096;

   // Signal 48: 2022-02-04 - DOWN - WIN
   signalTimes[47] = StringToTime("2022-02-04 13:30:00");
   signalDirections[47] = "DOWN";
   entryPrices[47] = 1813.129;
   exitPrices[47] = 1805.954;

   // Signal 49: 2022-01-07 - DOWN - LOSS
   signalTimes[48] = StringToTime("2022-01-07 13:30:00");
   signalDirections[48] = "DOWN";
   entryPrices[48] = 1788.835;
   exitPrices[48] = 1788.762;
}

//+------------------------------------------------------------------+
//| Execute trade based on signal                                    |
//+------------------------------------------------------------------+
void ExecuteTrade(int signalIndex)
{
   string comment = OrderComment + " #" + (string)(signalIndex + 1);
   bool result = false;
   
   // Determine actual direction (inverted if InvertSignals is true)
   string actualDirection = signalDirections[signalIndex];
   if(InvertSignals)
   {
      actualDirection = (signalDirections[signalIndex] == "UP") ? "DOWN" : "UP";
   }
   
   if(actualDirection == "UP")
   {
      result = trade.Buy(LotSize, _Symbol, 0, 0, 0, comment);
   }
   else if(actualDirection == "DOWN")
   {
      result = trade.Sell(LotSize, _Symbol, 0, 0, 0, comment);
   }
   
   if(result)
   {
      Print("Trade #", signalIndex + 1, " EXECUTED: ", actualDirection, 
            " (Original: ", signalDirections[signalIndex], ", Inverted: ", InvertSignals ? "YES" : "NO", ")",
            " | Signal time: ", TimeToString(signalTimes[signalIndex]));
      Print("Order ticket: ", trade.ResultOrder(), " | Deal: ", trade.ResultDeal());
      tradeExecuted[signalIndex] = true;
   }
   else
   {
      Print("Trade #", signalIndex + 1, " FAILED: ", trade.ResultRetcodeDescription());
      Print("Error code: ", trade.ResultRetcode());
   }
}

//+------------------------------------------------------------------+
//| Close all positions opened by this EA                            |
//+------------------------------------------------------------------+
void CloseAllPositions()
{
   for(int i = PositionsTotal() - 1; i >= 0; i--)
   {
      ulong ticket = PositionGetTicket(i);
      if(ticket > 0)
      {
         if(PositionGetInteger(POSITION_MAGIC) == MagicNumber)
         {
            if(trade.PositionClose(ticket))
            {
               Print("Position #", ticket, " CLOSED at NFP release (1-min candle HIGH)");
            }
            else
            {
               Print("Failed to close position #", ticket, ": ", trade.ResultRetcodeDescription());
            }
         }
      }
   }
}
//+------------------------------------------------------------------+
