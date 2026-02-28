//+------------------------------------------------------------------+
//|                                            TMA CG Data EA.mq5    |
//|                            Copyright 2026, Arrissa Technologies. |
//|                                            https://arrissa.trade |
//+------------------------------------------------------------------+
#property copyright "Copyright 2026, Arrissa Technologies."
#property link      "https://arrissa.trade"
#property version   "1.0"
#property strict

//--- Input parameters
input int      InpHalfLength        = 56;               // TMA Half Length
input ENUM_APPLIED_PRICE InpAppliedPrice = PRICE_WEIGHTED; // Applied Price
input double   InpBandsDeviations1  = 1.618;            // Bands Deviation Level 1
input double   InpBandsDeviations2  = 2.0;              // Bands Deviation Level 2
input double   InpBandsDeviations3  = 2.236;            // Bands Deviation Level 3
input double   InpBandsDeviations4  = 2.5;              // Bands Deviation Level 4
input double   InpBandsDeviations5  = 2.618;            // Bands Deviation Level 5
input double   InpBandsDeviations6  = 3.0;              // Bands Deviation Level 6
input double   InpBandsDeviations7  = 3.236;            // Bands Deviation Level 7
input bool     InpDrawOnChart       = true;             // Draw TMA Bands on Chart
input bool     InpDebugMode         = true;             // Enable Debug Output
input int      InpPrintIntervalSeconds = 5;             // Print Interval in Seconds

//--- API input parameters
input string AppBaseURL = "http://127.0.0.1"; //Base URL. Leave default if localhost or enter domain: https://arrissadata.com
string InpApiUrl = ""; // API URL (built in OnInit from AppBaseURL)
input bool     InpEnableApi = true;                    // Enable API communication
input int      InpApiPollingSeconds = 2;               // API polling interval in seconds

//--- Global variables
datetime last_print_time = 0;
datetime last_api_poll_time = 0;
bool api_processing_lock = false;

// Indicator handle
int g_indicatorHandle = INVALID_HANDLE;

// TMA buffers from indicator
double g_tmBuffer[];
double g_upBuffer1[], g_dnBuffer1[];
double g_upBuffer2[], g_dnBuffer2[];
double g_upBuffer3[], g_dnBuffer3[];
double g_upBuffer4[], g_dnBuffer4[];
double g_upBuffer5[], g_dnBuffer5[];
double g_upBuffer6[], g_dnBuffer6[];
double g_upBuffer7[], g_dnBuffer7[];

// Signal data
string g_currentZone = "neutral";  // premium or discount
string g_currentSignal = "neutral";
string g_currentArrow = "none";
double g_zonePercentage = 0.0;  // How far into the zone (0-100%)

//+------------------------------------------------------------------+
//| Expert initialization function                                   |
//+------------------------------------------------------------------+
int OnInit()
{
    InpApiUrl = AppBaseURL + "/tma-cg-api-v1/tma-cg-api.php";
    Print("TMA+CG Data EA initialized");
    Print("Half Length: ", InpHalfLength);
    Print("Applied Price: ", EnumToString(InpAppliedPrice));
    
    // Load TMA + CG indicator - Try with default parameters first
    ResetLastError();
    g_indicatorHandle = iCustom(_Symbol, PERIOD_CURRENT, "TMA + CG");
    
    if(g_indicatorHandle == INVALID_HANDLE)
    {
        int err = GetLastError();
        Print("WARNING: Failed with defaults (Error ", err, "), trying with HalfLength and Price...");
        
        // Try with just half length and applied price
        ResetLastError();
        g_indicatorHandle = iCustom(_Symbol, PERIOD_CURRENT, "TMA + CG",
                                     InpHalfLength,
                                     InpAppliedPrice);
    }
    
    if(g_indicatorHandle == INVALID_HANDLE)
    {
        int err = GetLastError();
        Print("WARNING: Failed (Error ", err, "), trying with all 9 parameters...");
        
        // Try with all parameters including deviation levels
        ResetLastError();
        g_indicatorHandle = iCustom(_Symbol, PERIOD_CURRENT, "TMA + CG",
                                     InpHalfLength,
                                     InpAppliedPrice,
                                     InpBandsDeviations1,
                                     InpBandsDeviations2,
                                     InpBandsDeviations3,
                                     InpBandsDeviations4,
                                     InpBandsDeviations5,
                                     InpBandsDeviations6,
                                     InpBandsDeviations7);
    }
    
    if(g_indicatorHandle == INVALID_HANDLE)
    {
        int err = GetLastError();
        Print("ERROR: Failed to load TMA + CG indicator!");
        Print("Error code: ", err);
        Print("Please check indicator parameters in TMA + CG.mq5 source file");
        Print("Current parameter attempt: HalfLength=", InpHalfLength, ", Price=", EnumToString(InpAppliedPrice));
        return(INIT_FAILED);
    }
    
    Print("TMA + CG indicator loaded successfully");
    Print("Debug Mode: ENABLED - Will print premium/discount zone every 5 seconds");
    
    // Set arrays as series
    ArraySetAsSeries(g_tmBuffer, true);
    ArraySetAsSeries(g_upBuffer1, true);
    ArraySetAsSeries(g_dnBuffer1, true);
    ArraySetAsSeries(g_upBuffer2, true);
    ArraySetAsSeries(g_dnBuffer2, true);
    ArraySetAsSeries(g_upBuffer3, true);
    ArraySetAsSeries(g_dnBuffer3, true);
    ArraySetAsSeries(g_upBuffer4, true);
    ArraySetAsSeries(g_dnBuffer4, true);
    ArraySetAsSeries(g_upBuffer5, true);
    ArraySetAsSeries(g_dnBuffer5, true);
    ArraySetAsSeries(g_upBuffer6, true);
    ArraySetAsSeries(g_dnBuffer6, true);
    ArraySetAsSeries(g_upBuffer7, true);
    ArraySetAsSeries(g_dnBuffer7, true);
    
    // Set timer for periodic updates
    EventSetTimer(1);
    
    // Calculate immediately on startup
    Print("Calculating initial TMA+CG data...");
    CalculateTmaCG();
    
    // Get current values
    double currentPrice = SymbolInfoDouble(_Symbol, SYMBOL_BID);
    double tmaMiddle = g_tmBuffer[0];
    
    // Print zone immediately
    Print("========================================");
    Print("*** INITIAL ZONE DATA ***");
    Print("[PRICE] ", DoubleToString(currentPrice, _Digits));
    Print("[TMA MIDDLE] ", DoubleToString(tmaMiddle, _Digits));
    Print("------------------------------------------------");
    
    if(currentPrice > tmaMiddle)
    {
        Print("[ZONE] PREMIUM");
        Print("[STATUS] Price is ABOVE TMA middle line");
        Print("[PERCENTAGE] ", DoubleToString(g_zonePercentage, 2), "% from TMA middle to upper band 7");
        Print("[MEANING] This is a PREMIUM zone (potential sell area)");
    }
    else if(currentPrice < tmaMiddle)
    {
        Print("[ZONE] DISCOUNT");
        Print("[STATUS] Price is BELOW TMA middle line");
        Print("[PERCENTAGE] ", DoubleToString(g_zonePercentage, 2), "% from TMA middle to lower band 7");
        Print("[MEANING] This is a DISCOUNT zone (potential buy area)");
    }
    else
    {
        Print("[ZONE] EQUILIBRIUM");
        Print("[STATUS] Price equals TMA middle line");
        Print("[PERCENTAGE] 0.00% (at equilibrium)");
    }
    
    Print("========================================");
    
    // Initialize print timer
    last_print_time = TimeCurrent();
    
    return(INIT_SUCCEEDED);
}

//+------------------------------------------------------------------+
//| Expert deinitialization function                                 |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
    EventKillTimer();
    
    // Release indicator handle
    if(g_indicatorHandle != INVALID_HANDLE)
        IndicatorRelease(g_indicatorHandle);
    
    // Clean up drawing objects
    if(InpDrawOnChart)
        DeleteAllDrawings();
    
    Print("TMA+CG Data EA deinitialized");
}

//+------------------------------------------------------------------+
//| Timer function                                                    |
//+------------------------------------------------------------------+
void OnTimer()
{
    datetime current_time = TimeCurrent();
    
    // Handle periodic printing
    if(current_time - last_print_time >= InpPrintIntervalSeconds)
    {
        last_print_time = current_time;
        
        // Calculate TMA+CG data
        CalculateTmaCG();
        
        // Draw on chart
        if(InpDrawOnChart)
            DrawTmaBands();
        
        // Always print premium/discount zone with percentage
        Print("[ZONE UPDATE] ", StringToUpper(g_currentZone), " (", DoubleToString(g_zonePercentage, 2), "%) | Price: ", DoubleToString(SymbolInfoDouble(_Symbol, SYMBOL_BID), _Digits), " | TMA: ", DoubleToString(g_tmBuffer[0], _Digits));
        
        // Print full data if debug enabled
        if(InpDebugMode)
            PrintTmaCGData();
    }
    
    // Handle API polling
    if(InpEnableApi && (current_time - last_api_poll_time >= InpApiPollingSeconds))
    {
        last_api_poll_time = current_time;
        PollApiForRequests();
    }
}

//+------------------------------------------------------------------+
//| OnTick - Update on every tick for live drawing                   |
//+------------------------------------------------------------------+
void OnTick()
{
    // Calculate TMA+CG data on every tick
    CalculateTmaCG();
    
    // Draw on chart if enabled
    if(InpDrawOnChart)
        DrawTmaBands();
}

//+------------------------------------------------------------------+
//| Calculate TMA+CG values by reading indicator buffers             |
//+------------------------------------------------------------------+
void CalculateTmaCG()
{
    if(g_indicatorHandle == INVALID_HANDLE)
    {
        Print("ERROR: Indicator handle is invalid");
        return;
    }
    
    int barsToRead = 100;
    
    // Copy indicator buffers
    // Buffer 0: TMA Middle
    if(CopyBuffer(g_indicatorHandle, 0, 0, barsToRead, g_tmBuffer) <= 0)
    {
        Print("ERROR: Failed to copy TMA middle buffer");
        return;
    }
    
    // Buffer 1-7: Upper bands
    CopyBuffer(g_indicatorHandle, 1, 0, barsToRead, g_upBuffer1);
    CopyBuffer(g_indicatorHandle, 2, 0, barsToRead, g_upBuffer2);
    CopyBuffer(g_indicatorHandle, 3, 0, barsToRead, g_upBuffer3);
    CopyBuffer(g_indicatorHandle, 4, 0, barsToRead, g_upBuffer4);
    CopyBuffer(g_indicatorHandle, 5, 0, barsToRead, g_upBuffer5);
    CopyBuffer(g_indicatorHandle, 6, 0, barsToRead, g_upBuffer6);
    CopyBuffer(g_indicatorHandle, 7, 0, barsToRead, g_upBuffer7);
    
    // Buffer 8-14: Lower bands
    CopyBuffer(g_indicatorHandle, 8, 0, barsToRead, g_dnBuffer1);
    CopyBuffer(g_indicatorHandle, 9, 0, barsToRead, g_dnBuffer2);
    CopyBuffer(g_indicatorHandle, 10, 0, barsToRead, g_dnBuffer3);
    CopyBuffer(g_indicatorHandle, 11, 0, barsToRead, g_dnBuffer4);
    CopyBuffer(g_indicatorHandle, 12, 0, barsToRead, g_dnBuffer5);
    CopyBuffer(g_indicatorHandle, 13, 0, barsToRead, g_dnBuffer6);
    CopyBuffer(g_indicatorHandle, 14, 0, barsToRead, g_dnBuffer7);
    
    // Determine premium/discount zone
    DetermineZone();
}

//+------------------------------------------------------------------+
//| Determine if price is in premium or discount zone                |
//+------------------------------------------------------------------+
void DetermineZone()
{
    double currentPrice = SymbolInfoDouble(_Symbol, SYMBOL_BID);
    double tmaMiddle = g_tmBuffer[0];
    
    // Reset signals
    g_currentZone = "neutral";
    g_currentSignal = "neutral";
    g_currentArrow = "none";
    g_zonePercentage = 0.0;
    
    if(tmaMiddle == 0 || tmaMiddle == EMPTY_VALUE)
    {
        Print("WARNING: TMA middle line has no valid value");
        return;
    }
    
    // Determine Premium/Discount zone with percentage
    if(currentPrice > tmaMiddle)
    {
        g_currentZone = "premium";
        g_currentSignal = "bullish";
        
        // Calculate percentage from TMA middle to upper band 7
        double upperBand7 = g_upBuffer7[0];
        if(upperBand7 > tmaMiddle)
        {
            g_zonePercentage = ((currentPrice - tmaMiddle) / (upperBand7 - tmaMiddle)) * 100.0;
            if(g_zonePercentage > 100.0) g_zonePercentage = 100.0; // Cap at 100%
        }
    }
    else if(currentPrice < tmaMiddle)
    {
        g_currentZone = "discount";
        g_currentSignal = "bearish";
        
        // Calculate percentage from TMA middle to lower band 7
        double lowerBand7 = g_dnBuffer7[0];
        if(lowerBand7 < tmaMiddle)
        {
            g_zonePercentage = ((tmaMiddle - currentPrice) / (tmaMiddle - lowerBand7)) * 100.0;
            if(g_zonePercentage > 100.0) g_zonePercentage = 100.0; // Cap at 100%
        }
    }
    else
    {
        g_currentZone = "equilibrium";
        g_currentSignal = "neutral";
        g_zonePercentage = 0.0;
    }
    
    // Additional signal detection for band touches
    if(currentPrice >= g_upBuffer1[0])
    {
        g_currentArrow = "down";  // Potential reversal from upper band
    }
    else if(currentPrice <= g_dnBuffer1[0])
    {
        g_currentArrow = "up";    // Potential reversal from lower band
    }
}

//+------------------------------------------------------------------+
//| Print TMA+CG data                                                |
//+------------------------------------------------------------------+
void PrintTmaCGData()
{
    Print("================================================");
    Print("TMA+CG Data - ", TimeToString(TimeCurrent(), TIME_DATE|TIME_MINUTES));
    Print("Symbol: ", _Symbol, " | Timeframe: ", EnumToString(PERIOD_CURRENT));
    Print("------------------------------------------------");
    Print("Current Price: ", DoubleToString(SymbolInfoDouble(_Symbol, SYMBOL_BID), _Digits));
    Print("TMA Middle:    ", DoubleToString(g_tmBuffer[0], _Digits));
    Print("------------------------------------------------");
    Print("ZONE: ", StringToUpper(g_currentZone), " (", DoubleToString(g_zonePercentage, 2), "%)");
    Print("  - Price is ", (g_currentZone == "premium" ? "ABOVE" : "BELOW"), " TMA middle line");
    if(g_currentZone == "premium")
        Print("  - Distance: ", DoubleToString(g_zonePercentage, 2), "% from TMA middle to upper band 7");
    else if(g_currentZone == "discount")
        Print("  - Distance: ", DoubleToString(g_zonePercentage, 2), "% from TMA middle to lower band 7");
    Print("------------------------------------------------");
    Print("Upper Bands:");
    Print("  Level 1 (", DoubleToString(InpBandsDeviations1, 3), "): ", DoubleToString(g_upBuffer1[0], _Digits));
    Print("  Level 2 (", DoubleToString(InpBandsDeviations2, 3), "): ", DoubleToString(g_upBuffer2[0], _Digits));
    Print("  Level 3 (", DoubleToString(InpBandsDeviations3, 3), "): ", DoubleToString(g_upBuffer3[0], _Digits));
    Print("  Level 4 (", DoubleToString(InpBandsDeviations4, 3), "): ", DoubleToString(g_upBuffer4[0], _Digits));
    Print("  Level 5 (", DoubleToString(InpBandsDeviations5, 3), "): ", DoubleToString(g_upBuffer5[0], _Digits));
    Print("  Level 6 (", DoubleToString(InpBandsDeviations6, 3), "): ", DoubleToString(g_upBuffer6[0], _Digits));
    Print("  Level 7 (", DoubleToString(InpBandsDeviations7, 3), "): ", DoubleToString(g_upBuffer7[0], _Digits));
    Print("------------------------------------------------");
    Print("Lower Bands:");
    Print("  Level 1 (", DoubleToString(InpBandsDeviations1, 3), "): ", DoubleToString(g_dnBuffer1[0], _Digits));
    Print("  Level 2 (", DoubleToString(InpBandsDeviations2, 3), "): ", DoubleToString(g_dnBuffer2[0], _Digits));
    Print("  Level 3 (", DoubleToString(InpBandsDeviations3, 3), "): ", DoubleToString(g_dnBuffer3[0], _Digits));
    Print("  Level 4 (", DoubleToString(InpBandsDeviations4, 3), "): ", DoubleToString(g_dnBuffer4[0], _Digits));
    Print("  Level 5 (", DoubleToString(InpBandsDeviations5, 3), "): ", DoubleToString(g_dnBuffer5[0], _Digits));
    Print("  Level 6 (", DoubleToString(InpBandsDeviations6, 3), "): ", DoubleToString(g_dnBuffer6[0], _Digits));
    Print("  Level 7 (", DoubleToString(InpBandsDeviations7, 3), "): ", DoubleToString(g_dnBuffer7[0], _Digits));
    Print("------------------------------------------------");
    Print("Signal: ", g_currentSignal);
    Print("Arrow: ", g_currentArrow);
    Print("================================================");
}

//+------------------------------------------------------------------+
//| Build JSON data for API                                          |
//+------------------------------------------------------------------+
string BuildTmaCGJson(string symbol, string timeframe)
{
    // Get current price
    double currentPrice = SymbolInfoDouble(symbol, SYMBOL_BID);
    
    string json = "{";
    json += "\"symbol\":\"" + symbol + "\",";
    json += "\"timeframe\":\"" + timeframe + "\",";
    json += "\"timestamp\":\"" + TimeToString(TimeCurrent(), TIME_DATE|TIME_MINUTES) + "\",";
    json += "\"zone\":\"" + g_currentZone + "\",";
    json += "\"percentage\":" + DoubleToString(g_zonePercentage, 2) + ",";
    json += "\"current_price\":" + DoubleToString(currentPrice, _Digits) + ",";
    json += "\"tma_middle\":" + DoubleToString(g_tmBuffer[0], _Digits) + ",";
    json += "\"upper_band_1\":" + DoubleToString(g_upBuffer1[0], _Digits) + ",";
    json += "\"lower_band_1\":" + DoubleToString(g_dnBuffer1[0], _Digits) + ",";
    json += "\"upper_band_7\":" + DoubleToString(g_upBuffer7[0], _Digits) + ",";
    json += "\"lower_band_7\":" + DoubleToString(g_dnBuffer7[0], _Digits);
    json += "}";
    
    return json;
}

//+------------------------------------------------------------------+
//| Calculate TMA+CG for any symbol (for API requests)              |
//+------------------------------------------------------------------+
string CalculateTmaCGForSymbol(string symbol, string timeframe)
{
    // Load indicator for the requested symbol
    int tempHandle = iCustom(symbol, PERIOD_CURRENT, "TMA + CG");
    
    if(tempHandle == INVALID_HANDLE)
    {
        // Try with parameters
        tempHandle = iCustom(symbol, PERIOD_CURRENT, "TMA + CG",
                            InpHalfLength,
                            InpAppliedPrice);
    }
    
    if(tempHandle == INVALID_HANDLE)
    {
        Print("ERROR: Failed to load TMA + CG indicator for symbol: ", symbol);
        // Return error JSON
        string errorJson = "{";
        errorJson += "\"symbol\":\"" + symbol + "\",";
        errorJson += "\"timeframe\":\"" + timeframe + "\",";
        errorJson += "\"error\":\"Failed to load indicator\",";
        errorJson += "\"zone\":\"unknown\",";
        errorJson += "\"percentage\":0.0,";
        errorJson += "\"current_price\":0.0,";
        errorJson += "\"tma_middle\":0.0,";
        errorJson += "\"upper_band_1\":0.0,";
        errorJson += "\"lower_band_1\":0.0,";
        errorJson += "\"upper_band_7\":0.0,";
        errorJson += "\"lower_band_7\":0.0";
        errorJson += "}";
        return errorJson;
    }
    
    // Temporary buffers for this symbol
    double tmBuffer[], upBuffer1[], dnBuffer1[], upBuffer7[], dnBuffer7[];
    ArraySetAsSeries(tmBuffer, true);
    ArraySetAsSeries(upBuffer1, true);
    ArraySetAsSeries(dnBuffer1, true);
    ArraySetAsSeries(upBuffer7, true);
    ArraySetAsSeries(dnBuffer7, true);
    
    int barsToRead = 100;
    
    // Copy buffers
    if(CopyBuffer(tempHandle, 0, 0, barsToRead, tmBuffer) <= 0)
    {
        IndicatorRelease(tempHandle);
        Print("ERROR: Failed to copy TMA buffer for symbol: ", symbol);
        return "{\"error\":\"Failed to copy buffer\"}";
    }
    
    CopyBuffer(tempHandle, 1, 0, barsToRead, upBuffer1);
    CopyBuffer(tempHandle, 7, 0, barsToRead, upBuffer7);
    CopyBuffer(tempHandle, 8, 0, barsToRead, dnBuffer1);
    CopyBuffer(tempHandle, 14, 0, barsToRead, dnBuffer7);
    
    // Get current price for the requested symbol
    double currentPrice = SymbolInfoDouble(symbol, SYMBOL_BID);
    double tmaMiddle = tmBuffer[0];
    
    // Calculate zone and percentage
    string zone = "equilibrium";
    double percentage = 0.0;
    
    if(currentPrice > tmaMiddle && tmaMiddle > 0)
    {
        zone = "premium";
        double upperBand7 = upBuffer7[0];
        if(upperBand7 > tmaMiddle)
        {
            percentage = ((currentPrice - tmaMiddle) / (upperBand7 - tmaMiddle)) * 100.0;
            if(percentage > 100.0) percentage = 100.0;
        }
    }
    else if(currentPrice < tmaMiddle && tmaMiddle > 0)
    {
        zone = "discount";
        double lowerBand7 = dnBuffer7[0];
        if(lowerBand7 < tmaMiddle)
        {
            percentage = ((tmaMiddle - currentPrice) / (tmaMiddle - lowerBand7)) * 100.0;
            if(percentage > 100.0) percentage = 100.0;
        }
    }
    
    // Get symbol digits for proper formatting
    int digits = (int)SymbolInfoInteger(symbol, SYMBOL_DIGITS);
    
    // Build JSON
    string json = "{";
    json += "\"symbol\":\"" + symbol + "\",";
    json += "\"timeframe\":\"" + timeframe + "\",";
    json += "\"timestamp\":\"" + TimeToString(TimeCurrent(), TIME_DATE|TIME_MINUTES) + "\",";
    json += "\"zone\":\"" + zone + "\",";
    json += "\"percentage\":" + DoubleToString(percentage, 2) + ",";
    json += "\"current_price\":" + DoubleToString(currentPrice, digits) + ",";
    json += "\"tma_middle\":" + DoubleToString(tmaMiddle, digits) + ",";
    json += "\"upper_band_1\":" + DoubleToString(upBuffer1[0], digits) + ",";
    json += "\"lower_band_1\":" + DoubleToString(dnBuffer1[0], digits) + ",";
    json += "\"upper_band_7\":" + DoubleToString(upBuffer7[0], digits) + ",";
    json += "\"lower_band_7\":" + DoubleToString(dnBuffer7[0], digits);
    json += "}";
    
    // Release temporary indicator handle
    IndicatorRelease(tempHandle);
    
    if(InpDebugMode)
        Print("Calculated TMA+CG for ", symbol, ": Zone=", zone, " (", DoubleToString(percentage, 2), "%)");
    
    return json;
}

//+------------------------------------------------------------------+
//| Poll API for pending requests                                   |
//+------------------------------------------------------------------+
void PollApiForRequests()
{
    if(!InpEnableApi)
        return;
    
    // Prevent overlapping requests
    if(api_processing_lock)
    {
        if(InpDebugMode)
            Print("API request already in progress, skipping poll");
        return;
    }
    
    char result_data[];
    char post_data[];
    string result_headers;
    int timeout = 15000;
    
    // Poll for requests (GET)
    int res = WebRequest(
        "GET",
        InpApiUrl,
        "",
        NULL,
        timeout,
        post_data,
        0,
        result_data,
        result_headers
    );
    
    if(res == -1)
    {
        int error = GetLastError();
        if(error == 4060)
        {
            Print("WebRequest not allowed. Add URL to allowed list: Tools->Options->Expert Advisors");
            Print("Add to allowed URLs: ", InpApiUrl);
        }
        else if(InpDebugMode)
        {
            Print("WebRequest error polling API: ", error);
        }
        return;
    }
    
    if(res == 200)
    {
        string response = CharArrayToString(result_data, 0, WHOLE_ARRAY, CP_UTF8);
        
        if(InpDebugMode)
            Print("API Poll Response: ", response);
        
        // Check if there's a pending request
        if(StringFind(response, "request_id") >= 0 && StringFind(response, "symbol") >= 0)
        {
            api_processing_lock = true;
            ProcessApiRequest(response);
            api_processing_lock = false;
        }
    }
}

//+------------------------------------------------------------------+
//| Process API request and send data                               |
//+------------------------------------------------------------------+
void ProcessApiRequest(string request_json)
{
    if(InpDebugMode)
        Print("Processing API request: ", request_json);
    
    // Extract request_id, symbol, and timeframe
    string request_id = ExtractJsonValue(request_json, "request_id");
    string requested_symbol = ExtractJsonValue(request_json, "symbol");
    string requested_timeframe = ExtractJsonValue(request_json, "timeframe");
    
    if(request_id == "" || requested_symbol == "")
    {
        if(InpDebugMode)
            Print("Invalid request: missing request_id or symbol");
        return;
    }
    
    if(InpDebugMode)
        Print("Request for symbol: ", requested_symbol, " timeframe: ", requested_timeframe);
    
    // Calculate data for the requested symbol (works for any symbol)
    string tma_json = CalculateTmaCGForSymbol(requested_symbol, requested_timeframe);
    
    // Send data to API
    SendDataToApi(request_id, requested_symbol, tma_json);
}

//+------------------------------------------------------------------+
//| Send data to API                                                 |
//+------------------------------------------------------------------+
void SendDataToApi(string request_id, string symbol, string tma_json)
{
    char result_data[];
    string result_headers;
    int timeout = 20000;
    
    // Build POST data
    string post_data = "request_id=" + request_id;
    post_data += "&symbol=" + symbol;
    post_data += "&tma_data=" + UrlEncode(tma_json);
    
    char post_array[];
    StringToCharArray(post_data, post_array, 0, StringLen(post_data), CP_UTF8);
    
    if(InpDebugMode)
        Print("Sending data to API for request_id: ", request_id);
    
    int res = WebRequest(
        "POST",
        InpApiUrl,
        "Content-Type: application/x-www-form-urlencoded\r\n",
        NULL,
        timeout,
        post_array,
        ArraySize(post_array),
        result_data,
        result_headers
    );
    
    if(res == -1)
    {
        int error = GetLastError();
        if(InpDebugMode)
            Print("WebRequest error sending data: ", error);
        return;
    }
    
    if(res == 200)
    {
        string response = CharArrayToString(result_data, 0, WHOLE_ARRAY, CP_UTF8);
        if(InpDebugMode)
            Print("API Send Response: ", response);
    }
}

//+------------------------------------------------------------------+
//| Extract JSON value                                               |
//+------------------------------------------------------------------+
string ExtractJsonValue(string json, string key)
{
    string search = "\"" + key + "\":\"";
    int start = StringFind(json, search);
    if(start < 0)
        return "";
    
    start += StringLen(search);
    int end = StringFind(json, "\"", start);
    if(end < 0)
        return "";
    
    return StringSubstr(json, start, end - start);
}

//+------------------------------------------------------------------+
//| URL Encode                                                        |
//+------------------------------------------------------------------+
string UrlEncode(string str)
{
    string result = "";
    for(int i = 0; i < StringLen(str); i++)
    {
        ushort ch = StringGetCharacter(str, i);
        if((ch >= 'A' && ch <= 'Z') || (ch >= 'a' && ch <= 'z') || (ch >= '0' && ch <= '9') ||
           ch == '-' || ch == '_' || ch == '.' || ch == '~')
        {
            result += ShortToString(ch);
        }
        else
        {
            result += StringFormat("%%%02X", ch);
        }
    }
    return result;
}

//+------------------------------------------------------------------+
//| Draw TMA bands on chart using polylines                          |
//+------------------------------------------------------------------+
void DrawTmaBands()
{
    int barsToShow = 100; // Show last 100 bars
    
    // Draw TMA Middle Line as curve
    DrawCurveLine("Middle", g_tmBuffer, barsToShow, clrDimGray, STYLE_DOT, 1);
    
    // Draw Upper Bands as curves
    DrawCurveLine("Upper1", g_upBuffer1, barsToShow, clrHotPink, STYLE_DOT, 1);
    DrawCurveLine("Upper2", g_upBuffer2, barsToShow, clrHotPink, STYLE_DOT, 1);
    DrawCurveLine("Upper3", g_upBuffer3, barsToShow, clrHotPink, STYLE_DOT, 1);
    DrawCurveLine("Upper4", g_upBuffer4, barsToShow, clrHotPink, STYLE_DOT, 1);
    DrawCurveLine("Upper5", g_upBuffer5, barsToShow, clrHotPink, STYLE_DOT, 1);
    DrawCurveLine("Upper6", g_upBuffer6, barsToShow, clrHotPink, STYLE_SOLID, 1);
    DrawCurveLine("Upper7", g_upBuffer7, barsToShow, clrHotPink, STYLE_SOLID, 2);
    
    // Draw Lower Bands as curves
    DrawCurveLine("Lower1", g_dnBuffer1, barsToShow, clrSpringGreen, STYLE_DOT, 1);
    DrawCurveLine("Lower2", g_dnBuffer2, barsToShow, clrSpringGreen, STYLE_DOT, 1);
    DrawCurveLine("Lower3", g_dnBuffer3, barsToShow, clrSpringGreen, STYLE_DOT, 1);
    DrawCurveLine("Lower4", g_dnBuffer4, barsToShow, clrSpringGreen, STYLE_DOT, 1);
    DrawCurveLine("Lower5", g_dnBuffer5, barsToShow, clrSpringGreen, STYLE_DOT, 1);
    DrawCurveLine("Lower6", g_dnBuffer6, barsToShow, clrSpringGreen, STYLE_SOLID, 1);
    DrawCurveLine("Lower7", g_dnBuffer7, barsToShow, clrSpringGreen, STYLE_SOLID, 2);
    
    // Draw signal info
    DrawSignalInfo();
    
    ChartRedraw();
}

//+------------------------------------------------------------------+
//| Draw curve line using multiple segments                          |
//+------------------------------------------------------------------+
void DrawCurveLine(string name, double &buffer[], int bars, color clr, int style, int width)
{
    // Draw line segments to create curve
    for(int i = 0; i < bars - 1; i++)
    {
        if(buffer[i] == 0 || buffer[i+1] == 0) continue;
        
        string objName = "TMA_" + name + "_" + IntegerToString(i);
        
        datetime time1 = iTime(_Symbol, PERIOD_CURRENT, i+1);
        datetime time2 = iTime(_Symbol, PERIOD_CURRENT, i);
        double price1 = buffer[i+1];
        double price2 = buffer[i];
        
        if(ObjectFind(0, objName) < 0)
        {
            ObjectCreate(0, objName, OBJ_TREND, 0, time1, price1, time2, price2);
            ObjectSetInteger(0, objName, OBJPROP_COLOR, clr);
            ObjectSetInteger(0, objName, OBJPROP_STYLE, style);
            ObjectSetInteger(0, objName, OBJPROP_WIDTH, width);
            ObjectSetInteger(0, objName, OBJPROP_RAY_RIGHT, false);
            ObjectSetInteger(0, objName, OBJPROP_BACK, true);
            ObjectSetInteger(0, objName, OBJPROP_SELECTABLE, false);
        }
        else
        {
            ObjectSetInteger(0, objName, OBJPROP_TIME, 0, time1);
            ObjectSetInteger(0, objName, OBJPROP_TIME, 1, time2);
            ObjectSetDouble(0, objName, OBJPROP_PRICE, 0, price1);
            ObjectSetDouble(0, objName, OBJPROP_PRICE, 1, price2);
        }
    }
}

//+------------------------------------------------------------------+
//| Draw signal information as text                                  |
//+------------------------------------------------------------------+
void DrawSignalInfo()
{
    int x = 10;
    int y = 20;
    
    // Zone label with percentage
    color zoneColor = (g_currentZone == "premium") ? clrHotPink : 
                      (g_currentZone == "discount") ? clrSpringGreen : clrGray;
    string zoneText = "ZONE: " + StringToUpper(g_currentZone) + " (" + DoubleToString(g_zonePercentage, 1) + "%)";
    CreateLabel("TMA_ZoneLabel", x, y, zoneText, zoneColor, 12);
    
    // Signal text
    CreateLabel("TMA_SignalLabel", x, y + 25, "Signal: " + g_currentSignal, clrWhite, 10);
    
    // Arrow indicator
    if(g_currentArrow == "up")
        CreateLabel("TMA_ArrowLabel", x, y + 45, "▲ REVERSAL UP", clrLime, 11);
    else if(g_currentArrow == "down")
        CreateLabel("TMA_ArrowLabel", x, y + 45, "▼ REVERSAL DOWN", clrRed, 11);
    else
        CreateLabel("TMA_ArrowLabel", x, y + 45, "● MONITORING", clrGray, 9);
    
    // Price info
    double currentPrice = SymbolInfoDouble(_Symbol, SYMBOL_BID);
    string priceInfo = "Price: " + DoubleToString(currentPrice, _Digits) + " | TMA: " + DoubleToString(g_tmBuffer[0], _Digits);
    CreateLabel("TMA_PriceLabel", x, y + 65, priceInfo, clrYellow, 9);
}

//+------------------------------------------------------------------+
//| Create text label                                                |
//+------------------------------------------------------------------+
void CreateLabel(string name, int x, int y, string text, color clr, int fontSize)
{
    string objName = "TMA_" + name;
    
    if(ObjectFind(0, objName) < 0)
    {
        ObjectCreate(0, objName, OBJ_LABEL, 0, 0, 0);
        ObjectSetInteger(0, objName, OBJPROP_CORNER, CORNER_LEFT_UPPER);
        ObjectSetInteger(0, objName, OBJPROP_XDISTANCE, x);
        ObjectSetInteger(0, objName, OBJPROP_YDISTANCE, y);
        ObjectSetInteger(0, objName, OBJPROP_COLOR, clr);
        ObjectSetInteger(0, objName, OBJPROP_FONTSIZE, fontSize);
        ObjectSetString(0, objName, OBJPROP_FONT, "Arial Bold");
        ObjectSetInteger(0, objName, OBJPROP_SELECTABLE, false);
        ObjectSetInteger(0, objName, OBJPROP_BACK, false);
    }
    
    ObjectSetString(0, objName, OBJPROP_TEXT, text);
    ObjectSetInteger(0, objName, OBJPROP_COLOR, clr);
}

//+------------------------------------------------------------------+
//| Delete all drawing objects                                       |
//+------------------------------------------------------------------+
void DeleteAllDrawings()
{
    for(int i = ObjectsTotal(0, 0, -1) - 1; i >= 0; i--)
    {
        string objName = ObjectName(0, i, 0, -1);
        if(StringFind(objName, "TMA_") == 0)
            ObjectDelete(0, objName);
    }
}
//+------------------------------------------------------------------+
