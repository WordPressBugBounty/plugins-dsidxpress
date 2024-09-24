zWidgetsConfig =
{
    script: [
    {
        name: "Map Search",
        location: "https://localhost/Scripts/PostCompile/MapSearch.js",
        type: "mapSearch",
        namespace: "ds.widget.view.mapsearch",
        cssClasses: "",
        published: true,
        dependencies:
        [
            {
                location: "https://maps.googleapis.com/maps/api/js?v=3&key=AIzaSyBBiuBC_EoHteciUM9P6g8D4mMBJ0zF8-U&sensor=false&callback=MapSearchMapCallback",
                scriptVariable: "$ScriptType$Dep1Script",
                depVariable: '$ScriptType$Dep1Finished'
            }
        ],
        fieldValues:
        [
            { name: "targetDomain", type: "userInput", label: "Domain", defaultValue: "localhost" },
            { name: "accountId", type: "userInput", label: "Account ID", defaultValue: "12" },
            { name: "searchSetupId", type: "userInput", label: "Search Setup ID", defaultValue: "64" },
            { name: "curDivID", type: "static", value: "divLocal$RandDT$_" },
            { name: "muteStyles", type: "userInput", label: "Mute Styles?", defaultValue: "false" },
            { name: "state", type: "userInput", label: "State", defaultValue: "" },
            { name: "city", type: "userInput", label: "City", defaultValue: "" },
            { name: "zip", type: "userInput", label: "Zipcode", defaultValue: "92626" },
            { name: "priceMin", type: "userInput", label: "Min Price", defaultValue: "250000" },
            { name: "priceMax", type: "userInput", label: "Max Price", defaultValue: "550000" },
            { name: "priceFloor", type: "userInput", label: "Price Floor", defaultValue: "100000" },
            { name: "priceCeiling", type: "userInput", label: "Price Ceiling", defaultValue: "1000000" },
            { name: "bedsMin", type: "userInput", label: "Min Beds", defaultValue: "2" },
            { name: "bathsMin", type: "userInput", label: "Min Baths", defaultValue: "2" },
            { name: "sqftMin", type: "userInput", label: "Min SqFt", defaultValue: "1500" },
            { name: "querySchema", type: "static", value: "HNIPilgrh/9PwdKmimpgPE05NfSeqIkyvHeXiSh+gUIUzKp3KXDCFoWJ/DzaOsYlntCSXtSk36hbB76URZk1Sirc9iLz3tiLPAN0SK/EbNCrr6XWxD7hAYVJcDwXtpN4"} //States,Cities,ZipCodes,ListingStatuses,PriceMin,PriceMax,BedsMin,BathsMin,ImprovedSqFtMin
        ],
        lookups:
        [
            { type: "status", label: "Status", name: "Active", value: "1" },
            { type: "status", label: "Status", name: "Pending", value: "2" },
            { type: "status", label: "Status", name: "Expired", value: "3" },
            { type: "status", label: "Status", name: "Sold", value: "4" },
            { type: "rowCount", label: "Row Count", name: "10", value: "10" },
            { type: "rowCount", label: "Row Count", name: "25", value: "25" },
            { type: "rowCount", label: "Row Count", name: "50", value: "50" },
            { type: "rowCount", label: "Row Count", name: "100", value: "100" },
            { type: "sort", label: "Sort", name: "Price $ to $$$", value: "0" },
            { type: "sort", label: "Sort", name: "Price $$$ to $", value: "1" }
        ]
    },
    {
        name: "Quick Search",
        location: "https://localhost/Scripts/PostCompile/QuickSearch.js",
        type: "quickSearch",
        namespace: "ds.widget.view.quicksearch",
        cssClasses: "qrcode_div,qrcode_text,qrcode_image,qrcode_table,qrcode_message_row,ios_image",
        published: true,
        fieldValues:
        [
            { name: "targetDomain", type: "userInput", label: "Domain", defaultValue: "localhost" },
            { name: "accountId", type: "userInput", label: "Account ID", defaultValue: "12" },
            { name: "searchSetupId", type: "userInput", label: "Search Setup ID", defaultValue: "64" },
            { name: "curDivID", type: "static", value: "divLocal$RandDT$_" },
            { name: "muteStyles", type: "userInput", label: "Mute Styles?", defaultValue: "false" },
            { name: "message", type: "userInput", label: "Message", defaultValue: "Search the MLS with your smartphone." }
        ],
        lookups:
        [
            { type: "productType", label: "Product", name: "IDXPress", value: "0" },
            { type: "productType", label: "Product", name: "SearchAgent", value: "1" }
        ]
    },
    {
        name: "QR Code",
        location: "https://localhost/Scripts/PostCompile/QRCode.js",//when we change anything in these js files, we need to incriment these versions
        type: "qrCode",
        namespace: "ds.widget.view.qrcode",
        cssClasses: "qrcode_div,qrcode_text,qrcode_image,qrcode_table,qrcode_message_row,ios_image",
        published: true,
        fieldValues:
        [
            { name: "targetDomain", type: "userInput", label: "Domain", defaultValue: "localhost" },
            { name: "accountId", type: "userInput", label: "Account ID", defaultValue: "12" },
            { name: "searchSetupId", type: "userInput", label: "Search Setup ID", defaultValue: "64" },
            { name: "curDivID", type: "static", value: "divLocal$RandDT$_" },
            { name: "muteStyles", type: "userInput", label: "Mute Styles?", defaultValue: "false" },
            { name: "message", type: "userInput", label: "Message", defaultValue: "Scan this QR code with your smartphone." }
        ]
    },
    {
        name: "Recent Status",
        location: "https://localhost/Scripts/PostCompile/RecentStatus.js",
        type: "recentStatus",
        namespace: "ds.widget.view.recentstatus",
        cssClasses: "",
        published: true,
        fieldValues:
        [
            { name: "targetDomain", type: "userInput", label: "Domain", defaultValue: "localhost" },
            { name: "accountId", type: "userInput", label: "Account ID", defaultValue: "12" },
            { name: "searchSetupId", type: "userInput", label: "Search Setup ID", defaultValue: "64" },
            { name: "curDivID", type: "static", value: "divLocal$RandDT$_" },
            { name: "muteStyles", type: "userInput", label: "Mute Styles?", defaultValue: "false" },
            { name: "curTitle", type: "userInput", label: "Title", defaultValue: "Recently Sold Homes In Your City" },
            { name: "rowCount", type: "userInput", label: "# of Rows", defaultValue: "5" },
            { name: "community", type: "userInput", label: "Community", defaultValue: "" },
            { name: "state", type: "userInput", label: "State", defaultValue: "CA" },
            { name: "city", type: "userInput", label: "City", defaultValue: "Irvine" },
            { name: "zip", type: "userInput", label: "Zipcode", defaultValue: "" },
            { name: "linkTitle", type: "userInput", label: "Link Title", defaultValue: "More sold homes in your city" },
            { name: "querySchema", type: "static", value: "MmZDz28oMETkfu/J7uVDj5me5CwqyWZUYbd0g3HEW8Ar73U98rI41MBxO894vgPN"} //Communities,Cities,ZipCodes,ListingStatuses
                                                                                                                            
        ],
        lookups:
        [
            { type: "status", label: "Status", name: "Active", value: "1" },
            { type: "status", label: "Status", name: "Backup", value: "2" },
            { type: "status", label: "Status", name: "Pending", value: "4" },
            { type: "status", label: "Status", name: "Sold", value: "8" }
        ]
    },
    {
        name: "Registration",
        location: "https://localhost/Scripts/PostCompile/Registration.js",
        type: "registration",
        namespace: "ds.widget.view.registration",
        cssClasses: "",
        published: true,
        fieldValues:
        [
            { name: "targetDomain", type: "userInput", label: "Domain", defaultValue: "localhost" },
            { name: "accountId", type: "userInput", label: "Account ID", defaultValue: "12" },
            { name: "searchSetupId", type: "userInput", label: "Search Setup ID", defaultValue: "64" },
            { name: "curDivID", type: "static", value: "divLocal$RandDT$_" },
            { name: "muteStyles", type: "userInput", label: "Mute Styles?", defaultValue: "false" }
        ]
    },
    {
        name: "Slideshow",
        location: "https://localhost/Scripts/PostCompile/Slideshow.js",
        type: "slideshow",
        namespace: "ds.widget.view.slideshow",
        cssClasses: "",
        published: true,
        fieldValues:
        [
            { name: "targetDomain", type: "userInput", label: "Domain", defaultValue: "localhost" },
            { name: "accountId", type: "userInput", label: "Account ID", defaultValue: "12" },
            { name: "searchSetupId", type: "userInput", label: "Search Setup ID", defaultValue: "64" },
            { name: "muteStyles", type: "userInput", label: "Mute Styles?", defaultValue: "false" },
            { name: "horzCount", type: "userInput", label: "Horizontal Count", defaultValue: "4" },
            { name: "maxPrice", type: "userInput", label: "Max Price", defaultValue: "350000" },
            { name: "state", type: "userInput", label: "State", defaultValue: "CA" },
            { name: "city", type: "userInput", label: "City", defaultValue: "Irvine" },
            { name: "zip", type: "userInput", label: "Zipcode", defaultValue: "" },
            { name: "curDivID", type: "static", value: "divLocal$RandDT$_" },
            { name: "querySchema", type: "static", value: "12dGTTViUjEzC1rrNlw6Lq6A6wZQlgBarlIcucpGTkQrUP3gCimYF6deRFaavu2IbPpaOkZ9I4K42QaAhLVEcA==" } //Cities,ZipCodes,ListingStatuses,PriceMin,PriceMax
        ]
    }
    ]
};