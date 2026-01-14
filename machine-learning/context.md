we have a CSH that shows the price of gold from 2014 to 2026 XAUUSD

sample:
 "Date","Price","Open","High","Low","Vol.","Change %"
"01/09/2026","4,471.67","4,475.84","4,477.70","4,452.97","","-0.11%"
"01/08/2026","4,476.73","4,453.04","4,479.80","4,407.48","","0.53%"

date format MM/DD/YYY

WE WANT TO CREATE DATA SERIES FROM THE DATA TO KNOW WHAT EVENTS FOR USD WERE AVAILABLE FOR THAT DAY
SO AT EACH DAYS START WE WILL GET EVENTS HAPPENING THAT DAY EXAMPLE on the 01/09/2026 form the market data

get future events that day http://127.0.0.1/news-api-v1/news-api.php?api_key=arr_b03bcfb76b4e904d&period=future&future_limit=today&pretend_date=2026-01-09&pretend_time=00:01&currency=USD&time_zone=NY&must_have=forecast_value,previous_value&avoid_duplicated=true&ignore_weekends=true&event_id=VPRWG,JCDYM,ZBEYU,GKORG,LUXEM,YHIYY,MXSBY,PIIRP,EEWPQ,ASXLP,ISFDE,YFVMV,YPAXY,RQUMP,WDLPA,FCHGZ,HKJPS,LUNIH,LIFLX,ZVAPL,HMUGW,ZORRY,ALMVK,BRHWF,IEDWO,BZLYI,XWBVZ,ZJOIV,FLPVV,ZGZEB,PEZJS,OMHBG,COJRY,FLANG,VLBVK,RIHAG,LOYOG,YCGKV,ZNLMN,VLJYS,VCOYI,LCSFP,KWQNJ,NPAUL,MEVPW,KSCVD,AGYKR,LCYVM,JMVUQ,PQRUD,ISCRG,FJRUD,GTPRZ,UYGJS,FNCVQ,MUQEC,OPLIH,YKFVQ,MXCLU,WSESU,OSYCZ,YFWBM,LTLVK,VYXGI,LCFMG,LHXZU,JXZHS,SKLTV,DEVZR,FUHNP,YJLZW,WBZHM,QSBCI,BRIOI

get future events for tomorrow http://127.0.0.1/news-api-v1/news-api.php?api_key=arr_b03bcfb76b4e904d&period=future&future_limit=tomorrow&pretend_date=2026-01-09&pretend_time=00:01&currency=USD&time_zone=NY&must_have=forecast_value,previous_value&avoid_duplicated=true&ignore_weekends=true&event_id=VPRWG,JCDYM,ZBEYU,GKORG,LUXEM,YHIYY,MXSBY,PIIRP,EEWPQ,ASXLP,ISFDE,YFVMV,YPAXY,RQUMP,WDLPA,FCHGZ,HKJPS,LUNIH,LIFLX,ZVAPL,HMUGW,ZORRY,ALMVK,BRHWF,IEDWO,BZLYI,XWBVZ,ZJOIV,FLPVV,ZGZEB,PEZJS,OMHBG,COJRY,FLANG,VLBVK,RIHAG,LOYOG,YCGKV,ZNLMN,VLJYS,VCOYI,LCSFP,KWQNJ,NPAUL,MEVPW,KSCVD,AGYKR,LCYVM,JMVUQ,PQRUD,ISCRG,FJRUD,GTPRZ,UYGJS,FNCVQ,MUQEC,OPLIH,YKFVQ,MXCLU,WSESU,OSYCZ,YFWBM,LTLVK,VYXGI,LCFMG,LHXZU,JXZHS,SKLTV,DEVZR,FUHNP,YJLZW,WBZHM,QSBCI,BRIOI

get fitire events for next week get future events that day http://127.0.0.1/news-api-v1/news-api.php?api_key=arr_b03bcfb76b4e904d&period=future&future_limit=next-week&pretend_date=2026-01-09&pretend_time=00:01&currency=USD&time_zone=NY&must_have=forecast_value,previous_value&avoid_duplicated=true&ignore_weekends=true&event_id=VPRWG,JCDYM,ZBEYU,GKORG,LUXEM,YHIYY,MXSBY,PIIRP,EEWPQ,ASXLP,ISFDE,YFVMV,YPAXY,RQUMP,WDLPA,FCHGZ,HKJPS,LUNIH,LIFLX,ZVAPL,HMUGW,ZORRY,ALMVK,BRHWF,IEDWO,BZLYI,XWBVZ,ZJOIV,FLPVV,ZGZEB,PEZJS,OMHBG,COJRY,FLANG,VLBVK,RIHAG,LOYOG,YCGKV,ZNLMN,VLJYS,VCOYI,LCSFP,KWQNJ,NPAUL,MEVPW,KSCVD,AGYKR,LCYVM,JMVUQ,PQRUD,ISCRG,FJRUD,GTPRZ,UYGJS,FNCVQ,MUQEC,OPLIH,YKFVQ,MXCLU,WSESU,OSYCZ,YFWBM,LTLVK,VYXGI,LCFMG,LHXZU,JXZHS,SKLTV,DEVZR,FUHNP,YJLZW,WBZHM,QSBCI,BRIOI

example json you will recive
{
    "response_for": "USD future events (limited to today)",
    "vestor_data": {
        "nonfarm_payrolls": {
            "count": 1,
            "events": [
                {
                    "event_id": "6fcd93a591bdea091d3ec5fa938409dd83cadb85",
                    "event_name": "Nonfarm Payrolls (Dec)",
                    "event_date": "2026-01-09",
                    "event_time": "08:30:00",
                    "currency": "USD",
                    "forecast_value": "66000",
                    "actual_value": "TBD",
                    "previous_value": "56000",
                    "impact_level": "High",
                    "consistent_event_id": "VPRWG"
                }
            ]
        },
        "unemployment_rate": {
            "count": 1,
            "events": [
                {
                    "event_id": "05eab7c7bf82efa98c72e0004d5c26a963b4732d",
                    "event_name": "Unemployment Rate (Dec)",
                    "event_date": "2026-01-09",
                    "event_time": "08:30:00",
                    "currency": "USD",
                    "forecast_value": "4.5",
                    "actual_value": "TBD",
                    "previous_value": "4.5",
                    "impact_level": "High",
                    "consistent_event_id": "JCDYM"
                }
            ]
        },
        "average_hourly_earnings": {
            "count": 1,
            "events": [
                {
                    "event_id": "6e7d6dad8d41b9f7cfff0d75794ae93369cbea0b",
                    "event_name": "Average Hourly Earnings (YoY) (YoY) (Dec)",
                    "event_date": "2026-01-09",
                    "event_time": "08:30:00",
                    "currency": "USD",
                    "forecast_value": "3.6",
                    "actual_value": "TBD",
                    "previous_value": "3.6",
                    "impact_level": "Moderate",
                    "consistent_event_id": "GKORG"
                }
            ]
        },
        "michigan_consumer_sentiment": {
            "count": 1,
            "events": [
                {
                    "event_id": "ce5c12c7fcd992bd2eea52f03efd313b7df6ba0b",
                    "event_name": "Michigan Consumer Sentiment (Jan)",
                    "event_date": "2026-01-09",
                    "event_time": "10:00:00",
                    "currency": "USD",
                    "forecast_value": "53.5",
                    "actual_value": "TBD",
                    "previous_value": "52.9",
                    "impact_level": "Moderate",
                    "consistent_event_id": "LOYOG"
                }
            ]
        },
        "michigan_1_year_inflation_expectations": {
            "count": 1,
            "events": [
                {
                    "event_id": "0e6cf53fb9b1459d462cc41a1dac2d253b6febc6",
                    "event_name": "Michigan 1-Year Inflation Expectations (Jan)",
                    "event_date": "2026-01-09",
                    "event_time": "10:00:00",
                    "currency": "USD",
                    "forecast_value": "4.1",
                    "actual_value": "TBD",
                    "previous_value": "4.2",
                    "impact_level": "Moderate",
                    "consistent_event_id": "YCGKV"
                }
            ]
        },
        "michigan_5_year_inflation_expectations": {
            "count": 1,
            "events": [
                {
                    "event_id": "9cd97f9b2729696180fa351c2f8ed8dfbb1af81b",
                    "event_name": "Michigan 5-Year Inflation Expectations (Jan)",
                    "event_date": "2026-01-09",
                    "event_time": "10:00:00",
                    "currency": "USD",
                    "forecast_value": "3.3",
                    "actual_value": "TBD",
                    "previous_value": "3.2",
                    "impact_level": "Moderate",
                    "consistent_event_id": "ZNLMN"
                }
            ]
        },
        "housing_starts": {
            "count": 1,
            "events": [
                {
                    "event_id": "205a5d79fb33e90e41c4facb1c8b80b7b7c5cfa0",
                    "event_name": "Housing Starts (Sep)",
                    "event_date": "2026-01-09",
                    "event_time": "08:29:00",
                    "currency": "USD",
                    "forecast_value": "1330000",
                    "actual_value": "TBD",
                    "previous_value": "1307000",
                    "impact_level": "Moderate",
                    "consistent_event_id": "PQRUD"
                }
            ]
        },
        "building_permits": {
            "count": 1,
            "events": [
                {
                    "event_id": "32a86831172723c2b84a841049af7a8352c72197",
                    "event_name": "Building Permits (Sep)",
                    "event_date": "2026-01-09",
                    "event_time": "08:29:00",
                    "currency": "USD",
                    "forecast_value": "1350000",
                    "actual_value": "TBD",
                    "previous_value": "1330000",
                    "impact_level": "Moderate",
                    "consistent_event_id": "ISCRG"
                }
            ]
        }
    }
}

HOW TO CALCULATE THE STATES OF THE ECONOMY as followes

in the important-events.md you have "USD Impact (Forecast > Previous)"
1. determine if forecast_value > than previous_value if yes then 
| Nonfarm Payrolls | VPRWG | USD | 9 | Positive | if not the (use the assiged impact in the importantevents.md)
but if less tahn you take te b opposit megavive

2. do this for all and we see if that event if in a nagative or positive state

3. once we find positivity and negativity state we need now we alogn the sign to the weight in this example in the example of nonfam patyrollas the forecast ifs higher than previos wich is positive for USD

SO THE WIEGHT IS +9

4. cALCULATE AGGREAGTED WIEIGHT FOR THE events (aggretae the wights of all events)


5. come up with an aggregate = example -15

6. calculate aggregate percentage relative this. to do this we hypothitcally assume that each event if it was good enough it would weigh 10 or -10

so we count the muber of events we have 8 events in this example which give is a total of shuld be weight of 80. just because the aggreate is a minus it makes is -80

aggregaye  -15 devided by should be weight -80 x 100 we get calculate aggregate percentage relative 1hoich give us

-18.75 this is just an example remeber we just assume that the agreagte is -15

now this value -18.75 becomes our state of theeconomy. do this to calculate stats of exconomy for all the periods today tomorow next week based on the events aquyired if there ae no events then 0

* now isoing the

return json with XAU_USD Historical Data.csv get us what is the statis of the close
UP OR DOWN AND TO WHAT MAGITUDE

"Date","Price","Open","High","Low","Vol.","Change %"
"01/09/2026","4,471.67","4,475.84","4,477.70","4,452.97","","-0.11%"
"01/08/2026","4,476.73","4,453.04","4,479.80","4,407.48","","0.53%" 

DO NOT PUT PRICES CREATE A JSON 

yOU WILL CREATE THIS JSON FROM 2014 - 2024
YOU USE THE  XAU_USD Historical Data.csv AS YOUR SERIUS GUIDE. CREATE A NODE JS APP THAT DOES THIS ONE FILE NODE JS

{
    test data{date:2026-01=09
    today_state_of_the_ecoconomy:
    tomorrow_state_of_the_ecoconomy:
    next_week_state_of_the_ecoconomy:
    }
    evaluation{
        direction:UP/down
        magnitude: 0.53 (Change %)
    }


}