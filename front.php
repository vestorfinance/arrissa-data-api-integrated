<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>
   Arrissa Data - Market Data for AI Applications
  </title>
  <meta content="Self-hosted REST APIs for MT5 market data, economic calendars, and trading automation" name="description"/>
  <style>*,:before,:after{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246/.5);--tw-ring-offset-shadow:0 0#0000;--tw-ring-shadow:0 0#0000;--tw-shadow:0 0#0000;--tw-shadow-colored:0 0#0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }::backdrop{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246/.5);--tw-ring-offset-shadow:0 0#0000;--tw-ring-shadow:0 0#0000;--tw-shadow:0 0#0000;--tw-shadow-colored:0 0#0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }*,:before,:after{box-sizing:border-box;border-width:0;border-style:solid}:before,:after{--tw-content:""}html{line-height:1.5;-webkit-text-size-adjust:100%;-moz-tab-size:4;-o-tab-size:4;tab-size:4;font-family:Inter,sans-serif;font-feature-settings:normal;font-variation-settings:normal;-webkit-tap-highlight-color:transparent}body{margin:0;line-height:inherit}a{color:inherit;text-decoration:inherit}code,pre{font-family:JetBrains Mono,monospace;font-feature-settings:normal;font-variation-settings:normal;font-size:1em}button{font-family:inherit;font-feature-settings:inherit;font-variation-settings:inherit;font-weight:inherit;line-height:inherit;letter-spacing:inherit;color:inherit;margin:0;padding:0}button{text-transform:none}button{-webkit-appearance:button;background-color:transparent;background-image:none}::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}h1,h2,h3,p,pre{margin:0}ol,ul{list-style:none;margin:0;padding:0}button{cursor:pointer}:disabled{cursor:default}svg{display:block;vertical-align:middle}:root{--background:0 0% 6%;--foreground:210 40% 98%;--card:0 0% 12%;--card-foreground:210 40% 98%;--popover:222 47% 7%;--popover-foreground:210 40% 98%;--primary:244 58% 60%;--primary-foreground:222 47% 4%;--secondary:0 0% 12%;--secondary-foreground:210 40% 98%;--muted:222 30% 15%;--muted-foreground:215 20% 55%;--accent:244 58% 60%;--accent-foreground:222 47% 4%;--destructive:0 84% 60%;--destructive-foreground:210 40% 98%;--border:0 0% 23%;--input:222 30% 18%;--ring:187 100% 42%;--radius:.75rem;--glow:244 58% 60%;--surface:222 47% 6%;--surface-elevated:222 47% 9%;--gradient-start:244 58% 60%;--gradient-end:244 58% 60%;--success:142 76% 36%;--warning:38 92% 50%}*{border-color:hsl(var(--border))}html{scroll-behavior:smooth;overflow-x:hidden}body{background-color:hsl(var(--background));color:hsl(var(--foreground));-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;font-family:Inter,sans-serif;overflow-x:hidden;width:100%;max-width:100vw}.container{width:100%;max-width:100%;padding-left:1rem;padding-right:1rem;box-sizing:border-box}@media (min-width:1400px){.container{max-width:1400px;margin-left:auto;margin-right:auto}}.gradient-text{-webkit-background-clip:text;background-clip:text;color:transparent;background-image:linear-gradient(135deg,hsl(var(--primary)),hsl(var(--gradient-end)))}.gradient-border{position:relative;background:hsl(var(--card));border-radius:var(--radius)}.gradient-border:before{content:"";position:absolute;top:0;right:0;bottom:0;left:0;padding:1px;border-radius:inherit;background:linear-gradient(135deg,hsl(var(--primary)/.5),hsl(var(--primary)/.1));-webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);-webkit-mask-composite:xor;mask-composite:exclude;pointer-events:none}.glow{box-shadow:0 0 40px hsl(var(--glow)/.15),0 0 80px hsl(var(--glow)/.1)}.glow-text{text-shadow:0 0 30px hsl(var(--primary)/.5)}.grid-pattern{background-image:linear-gradient(hsl(var(--border)/.3) 1px,transparent 1px),linear-gradient(90deg,hsl(var(--border)/.3) 1px,transparent 1px);background-size:60px 60px}.code-block{overflow-x:auto;border-radius:var(--radius);background-color:hsl(var(--surface));padding:1rem;font-family:JetBrains Mono,monospace;font-size:.875rem;line-height:1.25rem;border:1px solid hsl(var(--border));width:100%;box-sizing:border-box}.code-block pre{margin:0;white-space:pre;overflow-x:auto;-webkit-overflow-scrolling:touch}.code-block code{display:block;min-width:fit-content;font-size:.75rem}@media (max-width:768px){.code-block{font-size:.75rem;padding:.75rem}.code-block code{font-size:.7rem}}@keyframes float{0%,to{transform:translateY(0)}50%{transform:translateY(-10px)}}.animate-fade-up{animation:fadeUp .6s ease-out forwards;opacity:0}@keyframes fadeUp{0%{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}.animate-delay-100{animation-delay:.1s}.animate-delay-200{animation-delay:.2s}.animate-delay-300{animation-delay:.3s}.animate-delay-400{animation-delay:.4s}.fixed{position:fixed}.absolute{position:absolute}.relative{position:relative}.-inset-4{top:-1rem;right:-1rem;bottom:-1rem;left:-1rem}.inset-0{top:0;right:0;bottom:0;left:0}.-top-4{top:-1rem}.bottom-0{bottom:0}.bottom-1\/4{bottom:25%}.left-0{left:0}.left-1\/2{left:50%}.left-1\/4{left:25%}.right-0{right:0}.right-1\/4{right:25%}.top-0{top:0}.top-1\/4{top:25%}.z-10{z-index:10}.z-50{z-index:50}.z-\[100\]{z-index:100}.-mx-2{margin-left:-.5rem;margin-right:-.5rem}.mx-auto{margin-left:auto;margin-right:auto}.mb-10{margin-bottom:2.5rem}.mb-16{margin-bottom:4rem}.mb-2{margin-bottom:.5rem}.mb-4{margin-bottom:1rem}.mb-6{margin-bottom:1.5rem}.mb-8{margin-bottom:2rem}.ml-4{margin-left:1rem}.mt-2{margin-top:.5rem}.mt-8{margin-top:2rem}.inline-block{display:inline-block}.flex{display:flex}.inline-flex{display:inline-flex}.grid{display:grid}.hidden{display:none}.h-10{height:2.5rem}.h-12{height:3rem}.h-14{height:3.5rem}.h-16{height:4rem}.h-2{height:.5rem}.h-3{height:.75rem}.h-4{height:1rem}.h-5{height:1.25rem}.h-6{height:1.5rem}.h-9{height:2.25rem}.h-96{height:24rem}.max-h-screen{max-height:100vh}.min-h-screen{min-height:100vh}.w-10{width:2.5rem}.w-12{width:3rem}.w-2{width:.5rem}.w-3{width:.75rem}.w-4{width:1rem}.w-5{width:1.25rem}.w-6{width:1.5rem}.w-8{width:2rem}.w-96{width:24rem}.w-full{width:100%}.max-w-2xl{max-width:42rem}.max-w-3xl{max-width:48rem}.max-w-4xl{max-width:56rem}.max-w-5xl{max-width:64rem}.flex-shrink-0{flex-shrink:0}.-translate-x-1\/2{--tw-translate-x:-50%;transform:translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skew(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}@keyframes pulse{50%{opacity:.5}0%,to{opacity:1}}.animate-pulse{animation:pulse 2s cubic-bezier(.4,0,.6,1) infinite}.select-none{-webkit-user-select:none;-moz-user-select:none;user-select:none}.grid-cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}.flex-col{flex-direction:column}.flex-col-reverse{flex-direction:column-reverse}.items-center{align-items:center}.justify-center{justify-content:center}.justify-between{justify-content:space-between}.gap-1{gap:.25rem}.gap-12{gap:3rem}.gap-2{gap:.5rem}.gap-3{gap:.75rem}.gap-4{gap:1rem}.gap-6{gap:1.5rem}.gap-8{gap:2rem}.space-y-2>:not([hidden])~:not([hidden]){--tw-space-y-reverse:0;margin-top:calc(.5rem*calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(.5rem*var(--tw-space-y-reverse))}.space-y-3>:not([hidden])~:not([hidden]){--tw-space-y-reverse:0;margin-top:calc(.75rem*calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(.75rem*var(--tw-space-y-reverse))}.space-y-4>:not([hidden])~:not([hidden]){--tw-space-y-reverse:0;margin-top:calc(1rem*calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(1rem*var(--tw-space-y-reverse))}.overflow-hidden{overflow:hidden}.overflow-x-auto{overflow-x:auto}.whitespace-nowrap{white-space:nowrap}.rounded{border-radius:.25rem}.rounded-2xl{border-radius:1rem}.rounded-full{border-radius:9999px}.rounded-lg{border-radius:var(--radius)}.rounded-md{border-radius:calc(var(--radius) - 2px)}.rounded-xl{border-radius:.75rem}.border{border-width:1px}.border-2{border-width:2px}.border-b{border-bottom-width:1px}.border-t{border-top-width:1px}.border-border{border-color:hsl(var(--border))}.border-primary\/50{border-color:hsl(var(--primary)/.5)}.bg-\[hsl\(199\,89\%\,48\%\)\]\/20{background-color:#0da2e733}.bg-background{background-color:hsl(var(--background))}.bg-background\/80{background-color:hsl(var(--background)/.8)}.bg-card{background-color:hsl(var(--card))}.bg-destructive\/60{background-color:hsl(var(--destructive)/.6)}.bg-primary{background-color:hsl(var(--primary))}.bg-primary\/10{background-color:hsl(var(--primary)/.1)}.bg-primary\/20{background-color:hsl(var(--primary)/.2)}.bg-secondary{background-color:hsl(var(--secondary))}.bg-secondary\/50{background-color:hsl(var(--secondary)/.5)}.bg-success\/10{background-color:hsl(var(--success)/.1)}.bg-success\/60{background-color:hsl(var(--success)/.6)}.bg-transparent{background-color:transparent}.bg-warning\/60{background-color:hsl(var(--warning)/.6)}.bg-gradient-to-b{background-image:linear-gradient(to bottom,var(--tw-gradient-stops))}.bg-gradient-to-br{background-image:linear-gradient(to bottom right,var(--tw-gradient-stops))}.bg-gradient-to-r{background-image:linear-gradient(to right,var(--tw-gradient-stops))}.from-background{--tw-gradient-from:hsl(var(--background)) var(--tw-gradient-from-position);--tw-gradient-to:hsl(var(--background)/0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from),var(--tw-gradient-to)}.from-primary{--tw-gradient-from:hsl(var(--primary)) var(--tw-gradient-from-position);--tw-gradient-to:hsl(var(--primary)/0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from),var(--tw-gradient-to)}.from-primary\/10{--tw-gradient-from:hsl(var(--primary)/.1) var(--tw-gradient-from-position);--tw-gradient-to:hsl(var(--primary)/0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from),var(--tw-gradient-to)}.from-primary\/20{--tw-gradient-from:hsl(var(--primary)/.2) var(--tw-gradient-from-position);--tw-gradient-to:hsl(var(--primary)/0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from),var(--tw-gradient-to)}.via-background{--tw-gradient-to:hsl(var(--background)/0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from),hsl(var(--background)) var(--tw-gradient-via-position),var(--tw-gradient-to)}.via-secondary\/10{--tw-gradient-to:hsl(var(--secondary)/0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from),hsl(var(--secondary)/.1) var(--tw-gradient-via-position),var(--tw-gradient-to)}.via-secondary\/20{--tw-gradient-to:hsl(var(--secondary)/0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from),hsl(var(--secondary)/.2) var(--tw-gradient-via-position),var(--tw-gradient-to)}.to-\[hsl\(199\,89\%\,48\%\)\]{--tw-gradient-to:hsl(199,89%,48%) var(--tw-gradient-to-position)}.to-\[hsl\(199\,89\%\,48\%\)\]\/10{--tw-gradient-to:hsl(199 89% 48%/.1) var(--tw-gradient-to-position)}.to-\[hsl\(199\,89\%\,48\%\)\]\/20{--tw-gradient-to:hsl(199 89% 48%/.2) var(--tw-gradient-to-position)}.to-background{--tw-gradient-to:hsl(var(--background)) var(--tw-gradient-to-position)}.to-primary\/5{--tw-gradient-to:hsl(var(--primary)/.05) var(--tw-gradient-to-position)}.p-4{padding:1rem}.p-6{padding:1.5rem}.p-8{padding:2rem}.px-10{padding-left:2.5rem;padding-right:2.5rem}.px-2{padding-left:.5rem;padding-right:.5rem}.px-3{padding-left:.75rem;padding-right:.75rem}.px-4{padding-left:1rem;padding-right:1rem}.py-1{padding-top:.25rem;padding-bottom:.25rem}.py-12{padding-top:3rem;padding-bottom:3rem}.py-2{padding-top:.5rem;padding-bottom:.5rem}.py-24{padding-top:6rem;padding-bottom:6rem}.pb-4{padding-bottom:1rem}.pt-16{padding-top:4rem}.pt-6{padding-top:1.5rem}.text-center{text-align:center}.text-2xl{font-size:1.5rem;line-height:2rem}.text-3xl{font-size:1.875rem;line-height:2.25rem}.text-4xl{font-size:2.25rem;line-height:2.5rem}.text-5xl{font-size:3rem;line-height:1}.text-lg{font-size:1.125rem;line-height:1.75rem}.text-sm{font-size:.875rem;line-height:1.25rem}.text-xl{font-size:1.25rem;line-height:1.75rem}.text-xs{font-size:.75rem;line-height:1rem}.font-bold{font-weight:700}.font-medium{font-weight:500}.font-semibold{font-weight:600}.leading-relaxed{line-height:1.625}.leading-tight{line-height:1.25}.tracking-tight{letter-spacing:-.025em}.text-\[hsl\(199\,89\%\,48\%\)\]{--tw-text-opacity:1;color:hsl(199 89% 48%/var(--tw-text-opacity,1))}.text-foreground{color:hsl(var(--foreground))}.text-foreground\/90{color:hsl(var(--foreground)/.9)}.text-muted-foreground{color:hsl(var(--muted-foreground))}.text-muted-foreground\/50{color:hsl(var(--muted-foreground)/.5)}.text-primary{color:hsl(var(--primary))}.text-primary-foreground{color:hsl(var(--primary-foreground))}.text-success{color:hsl(var(--success))}.opacity-30{opacity:.3}.opacity-50{opacity:.5}.shadow-xl{--tw-shadow:0 20px 25px -5px rgb(0 0 0/.1),0 8px 10px -6px rgb(0 0 0/.1);--tw-shadow-colored:0 20px 25px -5px var(--tw-shadow-color),0 8px 10px -6px var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow,0 0#0000),var(--tw-ring-shadow,0 0#0000),var(--tw-shadow)}.shadow-primary\/30{--tw-shadow-color:hsl(var(--primary)/.3);--tw-shadow:var(--tw-shadow-colored)}.ring-offset-background{--tw-ring-offset-color:hsl(var(--background))}.blur-\[128px\]{--tw-blur:blur(128px);filter:var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)}.blur-xl{--tw-blur:blur(24px);filter:var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)}.backdrop-blur-xl{--tw-backdrop-blur:blur(24px);-webkit-backdrop-filter:var(--tw-backdrop-blur) var(--tw-backdrop-brightness) var(--tw-backdrop-contrast) var(--tw-backdrop-grayscale) var(--tw-backdrop-hue-rotate) var(--tw-backdrop-invert) var(--tw-backdrop-opacity) var(--tw-backdrop-saturate) var(--tw-backdrop-sepia);backdrop-filter:var(--tw-backdrop-blur) var(--tw-backdrop-brightness) var(--tw-backdrop-contrast) var(--tw-backdrop-grayscale) var(--tw-backdrop-hue-rotate) var(--tw-backdrop-invert) var(--tw-backdrop-opacity) var(--tw-backdrop-saturate) var(--tw-backdrop-sepia)}.transition-all{transition-property:all;transition-timing-function:cubic-bezier(.4,0,.2,1)}.transition-colors{transition-property:color,background-color,border-color,text-decoration-color,fill,stroke;transition-timing-function:cubic-bezier(.4,0,.2,1);transition-duration:.15s}.transition-transform{transition-property:transform;transition-timing-function:cubic-bezier(.4,0,.2,1)}.duration-300{transition-duration:.3s}@keyframes enter{0%{opacity:var(--tw-enter-opacity,1);transform:translate3d(var(--tw-enter-translate-x,0),var(--tw-enter-translate-y,0),0) scale3d(var(--tw-enter-scale,1),var(--tw-enter-scale,1),var(--tw-enter-scale,1)) rotate(var(--tw-enter-rotate,0))}}@keyframes exit{to{opacity:var(--tw-exit-opacity,1);transform:translate3d(var(--tw-exit-translate-x,0),var(--tw-exit-translate-y,0),0) scale3d(var(--tw-exit-scale,1),var(--tw-exit-scale,1),var(--tw-exit-scale,1)) rotate(var(--tw-exit-rotate,0))}}.duration-300{animation-duration:.3s}.hover\:-translate-y-1:hover{--tw-translate-y:-.25rem;transform:translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skew(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.hover\:scale-105:hover{--tw-scale-x:1.05;--tw-scale-y:1.05;transform:translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skew(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.hover\:border-primary:hover{border-color:hsl(var(--primary))}.hover\:border-primary\/50:hover{border-color:hsl(var(--primary)/.5)}.hover\:bg-primary\/10:hover{background-color:hsl(var(--primary)/.1)}.hover\:bg-secondary:hover{background-color:hsl(var(--secondary))}.hover\:bg-secondary\/30:hover{background-color:hsl(var(--secondary)/.3)}.hover\:text-foreground:hover{color:hsl(var(--foreground))}.hover\:text-secondary-foreground:hover{color:hsl(var(--secondary-foreground))}.hover\:shadow-primary\/50:hover{--tw-shadow-color:hsl(var(--primary)/.5);--tw-shadow:var(--tw-shadow-colored)}.focus-visible\:outline-none:focus-visible{outline:2px solid transparent;outline-offset:2px}.focus-visible\:ring-2:focus-visible{--tw-ring-offset-shadow:var(--tw-ring-inset)0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow:var(--tw-ring-inset)0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow,0 0#0000)}.focus-visible\:ring-ring:focus-visible{--tw-ring-color:hsl(var(--ring))}.focus-visible\:ring-offset-2:focus-visible{--tw-ring-offset-width:2px}.disabled\:pointer-events-none:disabled{pointer-events:none}.disabled\:opacity-50:disabled{opacity:.5}.group:hover .group-hover\:bg-primary{background-color:hsl(var(--primary))}.group:hover .group-hover\:bg-primary\/20{background-color:hsl(var(--primary)/.2)}.group:hover .group-hover\:text-primary-foreground{color:hsl(var(--primary-foreground))}@keyframes accordion-up{0%{height:var(--radix-accordion-content-height)}to{height:0}}@keyframes accordion-down{0%{height:0}to{height:var(--radix-accordion-content-height)}}@media (min-width:640px){.sm\:bottom-0{bottom:0}.sm\:right-0{right:0}.sm\:top-auto{top:auto}.sm\:flex-row{flex-direction:row}.sm\:flex-col{flex-direction:column}}@media (min-width:768px){.md\:flex{display:flex}.md\:max-w-\[420px\]{max-width:420px}.md\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}.md\:flex-row{flex-direction:row}.md\:text-2xl{font-size:1.5rem;line-height:2rem}.md\:text-5xl{font-size:3rem;line-height:1}.md\:text-7xl{font-size:4.5rem;line-height:1}}@media (min-width:1024px){.lg\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}.lg\:grid-cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}.lg\:grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}}.\[\&_svg\]\:pointer-events-none svg{pointer-events:none}.\[\&_svg\]\:size-4 svg{width:1rem;height:1rem}.\[\&_svg\]\:shrink-0 svg{flex-shrink:0}

*{max-width:100%}html,body{overflow-x:hidden;position:relative}img,svg,video,canvas,audio,iframe,embed,object{max-width:100%;height:auto}.container,.max-w-2xl,.max-w-3xl,.max-w-4xl,.max-w-5xl{box-sizing:border-box;padding-left:1rem;padding-right:1rem}pre,code{max-width:100%;word-wrap:break-word;overflow-wrap:break-word}@media (max-width:768px){.relative{max-width:100vw}.absolute.-inset-4{display:none}.lg\:grid-cols-2>*,.md\:grid-cols-2>*{width:100%}.text-5xl,.md\:text-7xl{font-size:2rem!important;line-height:1.2!important}.text-4xl,.md\:text-5xl{font-size:1.75rem!important;line-height:1.2!important}}

@keyframes swipe-out-left{0%{transform:var(--y) translate(var(--swipe-amount-x));opacity:1}to{transform:var(--y) translate(calc(var(--swipe-amount-x) - 100%));opacity:0}}@keyframes swipe-out-right{0%{transform:var(--y) translate(var(--swipe-amount-x));opacity:1}to{transform:var(--y) translate(calc(var(--swipe-amount-x) + 100%));opacity:0}}@keyframes swipe-out-up{0%{transform:var(--y) translateY(var(--swipe-amount-y));opacity:1}to{transform:var(--y) translateY(calc(var(--swipe-amount-y) - 100%));opacity:0}}@keyframes swipe-out-down{0%{transform:var(--y) translateY(var(--swipe-amount-y));opacity:1}to{transform:var(--y) translateY(calc(var(--swipe-amount-y) + 100%));opacity:0}}@keyframes sonner-fade-in{0%{opacity:0;transform:scale(.8)}to{opacity:1;transform:scale(1)}}@keyframes sonner-fade-out{0%{opacity:1;transform:scale(1)}to{opacity:0;transform:scale(.8)}}@keyframes sonner-spin{0%{opacity:1}to{opacity:.15}}

/* Minimalist App Overrides */
*, *::before, *::after { box-shadow: none !important; text-shadow: none !important; }
.bg-gradient-to-r, .bg-gradient-to-br, .bg-gradient-to-b { background-image: none !important; }
.bg-gradient-to-r.from-primary { background: transparent !important; border: 1px solid hsl(0, 0%, 23%) !important; color: hsl(0, 0%, 100%) !important; }
.gradient-text { background: none !important; -webkit-text-fill-color: hsl(0, 0%, 100%) !important; background-clip: unset !important; color: hsl(0, 0%, 100%) !important; }
.gradient-border::before { display: none !important; }
.w-10.h-10.rounded-lg.bg-gradient-to-br { background: #ffffff !important; border-radius: 50% !important; }
.w-10.h-10.rounded-lg svg { color: #0f0f0f !important; }
button, .inline-flex { border-radius: 9999px !important; background: transparent !important; border: 1px solid hsl(0, 0%, 23%) !important; color: hsl(0, 0%, 100%) !important; }

/* Sign In button ONLY in nav - look like text, no border */
nav button.hover\:bg-secondary,
nav .inline-flex.hover\:bg-secondary {
    background: transparent !important;
    border: none !important;
    color: hsl(0, 0%, 100%) !important;
}

nav button.hover\:bg-secondary:hover,
nav .inline-flex.hover\:bg-secondary:hover {
    background: transparent !important;
    border: none !important;
    color: #a0a0a0 !important;
}

.gradient-border, .rounded-2xl { border-radius: 24px !important; }

/* Pricing elements - pill shaped with border */
.bg-secondary\/50, .bg-secondary\/50 {
    background: transparent !important;
    border: 1px solid hsl(0, 0%, 23%) !important;
    border-radius: 9999px !important;
}

.bg-secondary\/50:hover, .bg-secondary\/50:hover {
    background: hsl(0, 0%, 12%) !important;
    border-color: hsl(0, 0%, 23%) !important;
}

.hover\:scale-105:hover { transform: none !important; }
.animate-fade-up, .animate-pulse { animation: none !important; opacity: 1 !important; }
nav, nav.bg-background\/80, nav.fixed { 
    background-color: rgba(15, 15, 15, 0.8) !important; 
    backdrop-filter: blur(20px) !important; 
    -webkit-backdrop-filter: blur(20px) !important;
    border-bottom: 1px solid hsl(0, 0%, 23%) !important;
}

/* Remove border when scrolled/sticky */
nav.scrolled, nav.is-scrolled {
    border-bottom: none !important;
}
.code-block { background-color: transparent !important; }
.grid.lg\:grid-cols-2.gap-12.items-center { margin-bottom: 20px !important; }
.text-primary, .text-\[hsl\(199\,89\%\,48\%\)\] { color: hsl(0, 0%, 100%) !important; }
.bg-primary { background: transparent !important; border: 1px solid hsl(0, 0%, 23%) !important; }
button:hover, .inline-flex:hover { background: #ffffff !important; border-color: #ffffff !important; color: #0f0f0f !important; opacity: 1 !important; }
span.gradient-text { color: hsl(0, 0%, 100%) !important; }
.text-muted-foreground { color: #a0a0a0 !important; }

/* Best Value badge on pricing card - accent background with white text */
.absolute.-top-4.left-1\/2.-translate-x-1\/2.px-4.py-1.rounded-full.bg-gradient-to-r {
    background: hsl(244, 58%, 60%) !important;
    background-image: none !important;
    color: #ffffff !important;
}

.absolute.-top-4 svg, .absolute.-top-4.left-1\/2 svg {
    color: #ffffff !important;
}

/* Light theme button hover */
body.light-theme button:hover, 
body.light-theme .inline-flex:hover { 
    background: #0f0f0f !important; 
    border-color: #0f0f0f !important; 
    color: #ffffff !important; 
}

/* Product image responsive border radius */
.product-image-responsive {
    border-radius: 24px !important;
}

@media (max-width: 768px) {
    img.product-image-responsive {
        border-radius: 12px !important;
    }
}

/* Hide hamburger on desktop */
@media (min-width: 768px) {
    #mobile-menu-toggle {
        display: none !important;
    }
}

/* Mobile Menu Sidebar Styles */
.mobile-menu-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 998;
    opacity: 0;
    transition: opacity 0.3s;
}

.mobile-menu-overlay.active {
    display: block;
    opacity: 1;
}

.mobile-menu-sidebar {
    position: fixed;
    top: 0;
    left: -288px;
    width: 288px;
    height: 100vh;
    height: 100dvh;
    background-color: #0f0f0f;
    z-index: 999;
    transition: left 0.3s ease-in-out;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.mobile-menu-sidebar.open {
    left: 0;
}

.mobile-menu-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    width: 100%;
}

.mobile-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 28px;
    border-bottom: 1px solid #3a3a3a;
    flex-shrink: 0;
}

.close-btn {
    background: none;
    border: none;
    color: #ffffff;
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-btn svg {
    width: 24px;
    height: 24px;
    color: #ffffff;
    stroke: currentColor;
}

.close-btn:hover {
    background-color: #1a1a1a;
}

.mobile-menu-nav {
    flex: 1;
    padding: 12px 16px;
    overflow-y: auto;
    overflow-x: hidden;
}

.mobile-menu-link {
    padding: 12px 16px;
    color: #a0a0a0;
    text-decoration: none;
    border-radius: 9999px;
    transition: all 0.2s;
    font-size: 16px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.mobile-menu-link svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.mobile-menu-link:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #ffffff;
}

.mobile-menu-footer {
    padding: 24px;
    border-top: 1px solid #3a3a3a;
    flex-shrink: 0;
}

.mobile-menu-footer button {
    width: 100% !important;
    margin-bottom: 12px;
}

.mobile-menu-footer button:last-child {
    margin-bottom: 0;
}

@media (min-width: 768px) {
    .mobile-menu-sidebar,
    .mobile-menu-overlay {
        display: none !important;
    }
}

/* Light theme support */
@media (prefers-color-scheme: light) {
    :root {
        --background: 0 0% 100%;
        --foreground: 0 0% 6%;
        --card: 0 0% 98%;
        --card-foreground: 0 0% 6%;
        --primary: 244 58% 60%;
        --primary-foreground: 0 0% 100%;
        --secondary: 0 0% 96%;
        --secondary-foreground: 0 0% 6%;
        --muted: 0 0% 96%;
        --muted-foreground: 0 0% 40%;
        --accent: 244 58% 60%;
        --accent-foreground: 0 0% 100%;
        --border: 0 0% 90%;
        --input: 0 0% 90%;
        --ring: 244 58% 60%;
    }
    
    body {
        background-color: hsl(0, 0%, 100%);
        color: hsl(0, 0%, 6%);
    }
    
    .text-muted-foreground {
        color: hsl(0, 0%, 40%) !important;
    }
    
    .bg-background {
        background-color: hsl(0, 0%, 100%) !important;
    }
    
    nav, nav.bg-background\/80 {
        background-color: rgba(255, 255, 255, 0.8) !important;
        border-bottom: 1px solid hsl(0, 0%, 90%) !important;
    }
    
    .gradient-border, .rounded-2xl {
        border-color: hsl(0, 0%, 90%) !important;
    }
    
    button, .inline-flex {
        border-color: hsl(0, 0%, 90%) !important;
        color: hsl(0, 0%, 6%) !important;
    }
    
    button:hover, .inline-flex:hover {
        background: hsl(0, 0%, 6%) !important;
        border-color: hsl(0, 0%, 6%) !important;
        color: hsl(0, 0%, 100%) !important;
    }
    
    .bg-secondary\/50, .bg-secondary\/50 {
        border-color: hsl(0, 0%, 90%) !important;
    }
    
    .bg-secondary\/50:hover, .bg-secondary\/50:hover {
        background: hsl(0, 0%, 96%) !important;
        border-color: hsl(0, 0%, 90%) !important;
    }
}

body.light-theme {
    background-color: #ffffff !important;
    color: #111827 !important;
}

body.light-theme .bg-background,
body.light-theme main {
    background-color: #ffffff !important;
}

body.light-theme nav {
    background-color: rgba(255, 255, 255, 0.8) !important;
    border-bottom: 1px solid #e5e7eb !important;
}

body.light-theme .text-muted-foreground {
    color: #6b7280 !important;
}

body.light-theme .gradient-border,
body.light-theme .rounded-2xl {
    background-color: #ffffff !important;
    border-color: #e5e7eb !important;
}

body.light-theme button:not(#theme-toggle-btn):not(#mobile-menu-close),
body.light-theme .inline-flex {
    border-color: #e5e7eb !important;
    color: #111827 !important;
}

body.light-theme button:not(#theme-toggle-btn):not(#mobile-menu-close):hover,
body.light-theme .inline-flex:hover {
    background: #111827 !important;
    border-color: #111827 !important;
    color: #ffffff !important;
}

body.light-theme .mobile-menu-sidebar {
    background-color: #ffffff !important;
    border-right: 1px solid #e5e7eb !important;
}

body.light-theme .mobile-menu-header {
    border-bottom: 1px solid #e5e7eb !important;
}

body.light-theme .mobile-menu-header span {
    color: #111827 !important;
}

body.light-theme .mobile-menu-link {
    color: #6b7280 !important;
}

body.light-theme .mobile-menu-link:hover {
    background: rgba(0, 0, 0, 0.05) !important;
    color: #111827 !important;
}

body.light-theme .mobile-menu-link svg {
    color: #6b7280 !important;
    stroke: #6b7280 !important;
}

body.light-theme .mobile-menu-footer {
    border-top: 1px solid #e5e7eb !important;
}

body.light-theme .close-btn svg {
    color: #111827 !important;
    stroke: #111827 !important;
}

body.light-theme .theme-toggle-container {
    border-bottom: 1px solid #e5e7eb !important;
}

body.light-theme .theme-toggle-container span {
    color: #6b7280 !important;
}

body.light-theme #theme-toggle-btn {
    border-color: #e5e7eb !important;
    color: #111827 !important;
}

body.light-theme #theme-toggle-btn svg {
    color: #111827 !important;
    stroke: #111827 !important;
}
</style>
 </head>
 <body>
  <div id="root">
   <div aria-label="Notifications (F8)" role="region" style="pointer-events:none" tabindex="-1">
    <ol class="fixed top-0 z-[100] flex max-h-screen w-full flex-col-reverse p-4 sm:bottom-0 sm:right-0 sm:top-auto sm:flex-col md:max-w-[420px]" tabindex="-1">
    </ol>
   </div>
   <section aria-atomic="false" aria-label="Notifications alt+T" aria-live="polite" aria-relevant="additions text" tabindex="-1">
   </section>
   <main class="min-h-screen bg-background">
        <nav class="fixed top-0 left-0 right-0 z-50 bg-background/80 backdrop-blur-xl border-b border-border">
     <div class="container mx-auto px-4 h-16 flex items-center justify-between">
      <div class="flex items-center gap-2">
       <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary to-[hsl(199,89%,48%)] flex items-center justify-center">
        <svg class="lucide lucide-database w-5 h-5 text-primary-foreground" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
         <ellipse cx="12" cy="5" rx="9" ry="3">
         </ellipse>
         <path d="M3 5V19A9 3 0 0 0 21 19V5">
         </path>
         <path d="M3 12A9 3 0 0 0 21 12">
         </path>
        </svg>
       </div>
       <span class="text-xl font-bold tracking-tight">
        Arrissa
        <span class="gradient-text">
         Data
        </span>
       </span>
      </div>
      <div class="hidden md:flex items-center gap-8">
       <a class="text-muted-foreground hover:text-foreground transition-colors" href="#products">
        Products
       </a>
       <a class="text-muted-foreground hover:text-foreground transition-colors" href="#features">
        Features
       </a>
       <a class="text-muted-foreground hover:text-foreground transition-colors" href="#pricing">
        Pricing
       </a>
       <a class="text-muted-foreground hover:text-foreground transition-colors" href="#api">
        API Docs
       </a>
      </div>
      <div class="hidden md:flex items-center gap-3">
       <a href="/login" class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 hover:bg-secondary hover:text-secondary-foreground h-9 rounded-md px-3">
        Sign In
       </a>
       <a href="https://t.me/arrissa_ai" target="_blank" class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm ring-offset-background transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 bg-gradient-to-r from-primary to-[hsl(199,89%,48%)] text-primary-foreground font-semibold shadow-xl shadow-primary/30 hover:shadow-primary/50 hover:scale-105 h-9 rounded-md px-3">
        Get Started
       </a>
      </div>
      <button id="mobile-menu-toggle" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-foreground hover:bg-secondary transition-colors">
       <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
       </svg>
      </button>
     </div>
     <!-- Mobile Menu Sidebar -->
     <div id="mobile-menu-overlay" class="mobile-menu-overlay"></div>
     <div id="mobile-menu" class="mobile-menu-sidebar">
      <div class="mobile-menu-content">
       <div class="mobile-menu-header">
        <div class="flex items-center gap-2">
         <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: #ffffff;">
          <span class="font-bold text-lg" style="color: #0f0f0f;">A</span>
         </div>
         <span class="font-semibold text-lg tracking-tight" style="color: #ffffff;">Arrissa Data API</span>
        </div>
        <button id="mobile-menu-close" class="close-btn">
         <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
         </svg>
        </button>
       </div>
       <nav class="mobile-menu-nav">
        <a class="mobile-menu-link" href="#products">
         <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <rect x="3" y="3" width="7" height="7"></rect>
          <rect x="14" y="3" width="7" height="7"></rect>
          <rect x="14" y="14" width="7" height="7"></rect>
          <rect x="3" y="14" width="7" height="7"></rect>
         </svg>
         <span>Products</span>
        </a>
        <a class="mobile-menu-link" href="#features">
         <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
         </svg>
         <span>Features</span>
        </a>
        <a class="mobile-menu-link" href="#pricing">
         <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <line x1="12" y1="1" x2="12" y2="23"></line>
          <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
         </svg>
         <span>Pricing</span>
        </a>
        <a class="mobile-menu-link" href="#api">
         <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line>
          <line x1="16" y1="17" x2="8" y2="17"></line>
          <polyline points="10 9 9 9 8 9"></polyline>
         </svg>
         <span>API Docs</span>
        </a>
       </nav>
       <div class="mobile-menu-footer">
        <div class="theme-toggle-container" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding: 0;">
         <div style="display: flex; align-items: center; gap: 12px;">
          <button id="theme-toggle-btn" onclick="toggleTheme()" style="background: transparent; border: none; color: #ffffff; padding: 8px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
           <svg id="theme-icon" style="width: 20px; height: 20px; color: #a0a0a0;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
           </svg>
          </button>
          <span style="color: #a0a0a0; font-size: 14px; font-weight: 500;">Toggle Theme</span>
         </div>
        </div>
        <a href="/login" class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-all duration-300 focus-visible:outline-none hover:bg-secondary hover:text-secondary-foreground h-9 rounded-md px-3 w-full" style="background: transparent; border: 1px solid #3a3a3a; color: #ffffff;">
         Sign In
        </a>
        <a href="https://t.me/arrissa_ai" target="_blank" class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm ring-offset-background transition-all duration-300 focus-visible:outline-none h-9 rounded-md px-3 w-full" style="background: #4f46e5; color: #ffffff; border: 1px solid #4f46e5;">
         Get Started
        </a>
       </div>
      </div>
     </div>
    </nav>
    <section class="relative min-h-screen flex items-center justify-center pt-16 overflow-hidden">
     <div class="absolute inset-0 grid-pattern opacity-50">
     </div>
     <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-primary/20 rounded-full blur-[128px] animate-pulse">
     </div>
     <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-[hsl(199,89%,48%)]/20 rounded-full blur-[128px] animate-pulse" style="animation-delay:1s">
     </div>
     <div class="container mx-auto px-4 relative z-10">
      <div class="max-w-4xl mx-auto text-center">
       <div class="animate-fade-up inline-flex items-center gap-2 px-4 py-2 rounded-full bg-secondary border border-border mb-8">
        <svg class="lucide lucide-zap w-4 h-4 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
         <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z">
         </path>
        </svg>
        <span class="text-sm text-muted-foreground">
         Installation Included • Self-hosted • Zero data fees
        </span>
       </div>
       <h1 class="animate-fade-up animate-delay-100 text-5xl md:text-7xl font-bold leading-tight mb-6">
        Market Data for
        <span class="gradient-text glow-text">
         AI Applications
        </span>
       </h1>
       <p class="animate-fade-up animate-delay-200 text-xl md:text-2xl text-muted-foreground max-w-2xl mx-auto mb-10">
        Connect your MT5 to powerful REST APIs. Get real-time market data, economic calendars, and execute trades programmatically.
        <div class="animate-fade-up animate-delay-300 flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
         <a href="https://t.me/arrissa_ai" target="_blank" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg ring-offset-background transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 bg-gradient-to-r from-primary to-[hsl(199,89%,48%)] text-primary-foreground font-semibold shadow-xl shadow-primary/30 hover:shadow-primary/50 hover:scale-105 h-14 px-10 text-lg">
          Get Full Package — $1,999
          <svg class="lucide lucide-arrow-right w-5 h-5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
           <path d="M5 12h14">
           </path>
           <path d="m12 5 7 7-7 7">
           </path>
          </svg>
         </a>
         <a href="https://t.me/arrissa_ai" target="_blank" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg font-medium ring-offset-background transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border-2 border-primary/50 bg-transparent text-foreground hover:bg-primary/10 hover:border-primary h-14 px-10 text-lg">
          View API Docs
         </a>
        </div>
        <div class="animate-fade-up animate-delay-400 grid grid-cols-3 gap-8 max-w-2xl mx-auto">
         <div class="text-center">
          <div class="flex items-center justify-center gap-2 mb-2">
           <svg class="lucide lucide-globe w-5 h-5 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10">
            </circle>
            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20">
            </path>
            <path d="M2 12h20">
            </path>
           </svg>
           <span class="text-3xl font-bold text-foreground">
            4
           </span>
          </div>
          <span class="text-sm text-muted-foreground">
           Powerful APIs
          </span>
         </div>
         <div class="text-center">
          <div class="flex items-center justify-center gap-2 mb-2">
           <svg class="lucide lucide-server w-5 h-5 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
            <rect height="8" rx="2" ry="2" width="20" x="2" y="2">
            </rect>
            <rect height="8" rx="2" ry="2" width="20" x="2" y="14">
            </rect>
            <line x1="6" x2="6.01" y1="6" y2="6">
            </line>
            <line x1="6" x2="6.01" y1="18" y2="18">
            </line>
           </svg>
           <span class="text-3xl font-bold text-foreground">
            100%
           </span>
          </div>
          <span class="text-sm text-muted-foreground">
           Self-Hosted
          </span>
         </div>
         <div class="text-center">
          <div class="flex items-center justify-center gap-2 mb-2">
           <svg class="lucide lucide-zap w-5 h-5 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z">
            </path>
           </svg>
           <span class="text-3xl font-bold text-foreground">
            $0
           </span>
          </div>
          <span class="text-sm text-muted-foreground">
           Data Fees
          </span>
         </div>
        </div>
       </p>
      </div>
     </div>
    </section>

    <section class="py-16 relative">
     <div class="container mx-auto px-4">
      <div class="max-w-6xl mx-auto">
       <img src="/public/product-image.png" alt="Product Dashboard" class="w-full rounded-3xl border border-border shadow-2xl product-image-responsive" />
      </div>
     </div>
    </section>
    <section class="py-24 relative" id="products">
     <div class="absolute inset-0 bg-gradient-to-b from-background via-secondary/20 to-background">
     </div>
     <div class="container mx-auto px-4 relative z-10">
      <div class="text-center mb-16">
       <h2 class="text-4xl md:text-5xl font-bold mb-4">
        Our
        <span class="gradient-text">
         API Products
        </span>
       </h2>
       <p class="text-xl text-muted-foreground max-w-2xl mx-auto">
        Each API is designed to seamlessly integrate with n8n, Make, Zapier, or any custom application.
       </p>
      </div>
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
       <div class="gradient-border p-6 hover:scale-105 transition-transform duration-300 group" style="animation-delay:0s">
        <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/20 transition-colors">
         <svg class="lucide lucide-chart-column w-6 h-6 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <path d="M3 3v16a2 2 0 0 0 2 2h16">
          </path>
          <path d="M18 17V9">
          </path>
          <path d="M13 17V5">
          </path>
          <path d="M8 17v-3">
          </path>
         </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">
         Market Data API
        </h3>
        <p class="text-muted-foreground text-sm mb-4">
         Real-time and historical market data from MT5 in JSON format.
         <div class="text-3xl font-bold gradient-text mb-4">
          $699
         </div>
         <ul class="space-y-2 mb-6">
          <li class="flex items-center gap-2 text-sm text-muted-foreground">
           <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 6 9 17l-5-5">
            </path>
           </svg>
           Live tick data streaming
           <li class="flex items-center gap-2 text-sm text-muted-foreground">
            <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
             <path d="M20 6 9 17l-5-5">
             </path>
            </svg>
            Historical OHLC data
            <li class="flex items-center gap-2 text-sm text-muted-foreground">
             <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 6 9 17l-5-5">
              </path>
             </svg>
             Multiple timeframes
             <li class="flex items-center gap-2 text-sm text-muted-foreground">
              <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
               <path d="M20 6 9 17l-5-5">
               </path>
              </svg>
              Symbol information
             </li>
            </li>
           </li>
          </li>
         </ul>
         <a href="https://t.me/arrissa_ai" target="_blank" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg text-sm font-medium ring-offset-background duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border border-border bg-transparent hover:bg-secondary hover:text-secondary-foreground h-10 px-4 py-2 w-full group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
          Learn More
          <svg class="lucide lucide-arrow-right w-4 h-4" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
           <path d="M5 12h14">
           </path>
           <path d="m12 5 7 7-7 7">
           </path>
          </svg>
         </a>
        </p>
       </div>
       <div class="gradient-border p-6 hover:scale-105 transition-transform duration-300 group" style="animation-delay:0.1s">
        <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/20 transition-colors">
         <svg class="lucide lucide-calendar w-6 h-6 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <path d="M8 2v4">
          </path>
          <path d="M16 2v4">
          </path>
          <rect height="18" rx="2" width="18" x="3" y="4">
          </rect>
          <path d="M3 10h18">
          </path>
         </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">
         Economic Calendar API
        </h3>
        <p class="text-muted-foreground text-sm mb-4">
         Comprehensive economic events and news data for informed trading.
         <div class="text-3xl font-bold gradient-text mb-4">
          $699
         </div>
         <ul class="space-y-2 mb-6">
          <li class="flex items-center gap-2 text-sm text-muted-foreground">
           <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 6 9 17l-5-5">
            </path>
           </svg>
           Global economic events
           <li class="flex items-center gap-2 text-sm text-muted-foreground">
            <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
             <path d="M20 6 9 17l-5-5">
             </path>
            </svg>
            Impact ratings
            <li class="flex items-center gap-2 text-sm text-muted-foreground">
             <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 6 9 17l-5-5">
              </path>
             </svg>
             Historical data access
             <li class="flex items-center gap-2 text-sm text-muted-foreground">
              <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
               <path d="M20 6 9 17l-5-5">
               </path>
              </svg>
              Real-time updates
             </li>
            </li>
           </li>
          </li>
         </ul>
         <a href="https://t.me/arrissa_ai" target="_blank" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg text-sm font-medium ring-offset-background duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border border-border bg-transparent hover:bg-secondary hover:text-secondary-foreground h-10 px-4 py-2 w-full group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
          Learn More
          <svg class="lucide lucide-arrow-right w-4 h-4" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
           <path d="M5 12h14">
           </path>
           <path d="m12 5 7 7-7 7">
           </path>
          </svg>
         </a>
        </p>
       </div>
       <div class="gradient-border p-6 hover:scale-105 transition-transform duration-300 group" style="animation-delay:0.2s">
        <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/20 transition-colors">
         <svg class="lucide lucide-settings w-6 h-6 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z">
          </path>
          <circle cx="12" cy="12" r="3">
          </circle>
         </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">
         Orders API
        </h3>
        <p class="text-muted-foreground text-sm mb-4">
         Full MT5 trading capabilities exposed via REST endpoints.
         <div class="text-3xl font-bold gradient-text mb-4">
          $899
         </div>
         <ul class="space-y-2 mb-6">
          <li class="flex items-center gap-2 text-sm text-muted-foreground">
           <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 6 9 17l-5-5">
            </path>
           </svg>
           Open &amp; close positions
           <li class="flex items-center gap-2 text-sm text-muted-foreground">
            <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
             <path d="M20 6 9 17l-5-5">
             </path>
            </svg>
            Modify orders
            <li class="flex items-center gap-2 text-sm text-muted-foreground">
             <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 6 9 17l-5-5">
              </path>
             </svg>
             Account information
             <li class="flex items-center gap-2 text-sm text-muted-foreground">
              <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
               <path d="M20 6 9 17l-5-5">
               </path>
              </svg>
              Trade history
             </li>
            </li>
           </li>
          </li>
         </ul>
         <a href="https://t.me/arrissa_ai" target="_blank" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg text-sm font-medium ring-offset-background duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border border-border bg-transparent hover:bg-secondary hover:text-secondary-foreground h-10 px-4 py-2 w-full group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
          Learn More
          <svg class="lucide lucide-arrow-right w-4 h-4" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
           <path d="M5 12h14">
           </path>
           <path d="m12 5 7 7-7 7">
           </path>
          </svg>
         </a>
        </p>
       </div>
       <div class="gradient-border p-6 hover:scale-105 transition-transform duration-300 group" style="animation-delay:0.3s">
        <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/20 transition-colors">
         <svg class="lucide lucide-image w-6 h-6 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <rect height="18" rx="2" ry="2" width="18" x="3" y="3">
          </rect>
          <circle cx="9" cy="9" r="2">
          </circle>
          <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21">
          </path>
         </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">
         Chart Image API
        </h3>
        <p class="text-muted-foreground text-sm mb-4">
         Generate chart images on-demand for reports and analysis.
         <div class="text-3xl font-bold gradient-text mb-4">
          $499
         </div>
         <ul class="space-y-2 mb-6">
          <li class="flex items-center gap-2 text-sm text-muted-foreground">
           <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 6 9 17l-5-5">
            </path>
           </svg>
           Custom timeframes
           <li class="flex items-center gap-2 text-sm text-muted-foreground">
            <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
             <path d="M20 6 9 17l-5-5">
             </path>
            </svg>
            Indicator overlays
            <li class="flex items-center gap-2 text-sm text-muted-foreground">
             <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 6 9 17l-5-5">
              </path>
             </svg>
             Multiple styles
             <li class="flex items-center gap-2 text-sm text-muted-foreground">
              <svg class="lucide lucide-check w-4 h-4 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
               <path d="M20 6 9 17l-5-5">
               </path>
              </svg>
              High-resolution export
             </li>
            </li>
           </li>
          </li>
         </ul>
         <a href="https://t.me/arrissa_ai" target="_blank" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg text-sm font-medium ring-offset-background duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border border-border bg-transparent hover:bg-secondary hover:text-secondary-foreground h-10 px-4 py-2 w-full group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
          Learn More
          <svg class="lucide lucide-arrow-right w-4 h-4" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
           <path d="M5 12h14">
           </path>
           <path d="m12 5 7 7-7 7">
           </path>
          </svg>
         </a>
        </p>
       </div>
      </div>
     </div>
    </section>
    <section class="py-24 relative overflow-hidden" id="features">
     <div class="absolute top-0 right-0 w-96 h-96 bg-primary/10 rounded-full blur-[128px]">
     </div>
     <div class="container mx-auto px-4 relative z-10">
      <div class="text-center mb-16">
       <h2 class="text-4xl md:text-5xl font-bold mb-4">
        Why Choose
        <span class="gradient-text">
         Arrissa Data?
        </span>
       </h2>
       <p class="text-xl text-muted-foreground max-w-2xl mx-auto">
        Built for developers and traders who need reliable, self-hosted market data solutions.
       </p>
      </div>
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
       <div class="p-6 rounded-xl bg-card border border-border hover:border-primary/50 transition-all duration-300 hover:-translate-y-1">
        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center mb-4">
         <svg class="lucide lucide-server w-6 h-6 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <rect height="8" rx="2" ry="2" width="20" x="2" y="2">
          </rect>
          <rect height="8" rx="2" ry="2" width="20" x="2" y="14">
          </rect>
          <line x1="6" x2="6.01" y1="6" y2="6">
          </line>
          <line x1="6" x2="6.01" y1="18" y2="18">
          </line>
         </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">
         Self-Hosted
        </h3>
        <p class="text-muted-foreground">
         Deploy on your own infrastructure. Full control, no external dependencies.
        </p>
       </div>
       <div class="p-6 rounded-xl bg-card border border-border hover:border-primary/50 transition-all duration-300 hover:-translate-y-1">
        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center mb-4">
         <svg class="lucide lucide-shield w-6 h-6 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
          </path>
         </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">
         Zero Data Fees
        </h3>
        <p class="text-muted-foreground">
         No recurring charges for data access. Pay once, use forever.
        </p>
       </div>
       <div class="p-6 rounded-xl bg-card border border-border hover:border-primary/50 transition-all duration-300 hover:-translate-y-1">
        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center mb-4">
         <svg class="lucide lucide-code w-6 h-6 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <polyline points="16 18 22 12 16 6">
          </polyline>
          <polyline points="8 6 2 12 8 18">
          </polyline>
         </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">
         RESTful Design
        </h3>
        <p class="text-muted-foreground">
         Clean, intuitive JSON responses. Easy integration with any platform.
        </p>
       </div>
       <div class="p-6 rounded-xl bg-card border border-border hover:border-primary/50 transition-all duration-300 hover:-translate-y-1">
        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center mb-4">
         <svg class="lucide lucide-zap w-6 h-6 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z">
          </path>
         </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">
         Real-Time Data
        </h3>
        <p class="text-muted-foreground">
         Live market data streaming with minimal latency directly from MT5.
        </p>
       </div>
       <div class="p-6 rounded-xl bg-card border border-border hover:border-primary/50 transition-all duration-300 hover:-translate-y-1">
        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center mb-4">
         <svg class="lucide lucide-database w-6 h-6 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <ellipse cx="12" cy="5" rx="9" ry="3">
          </ellipse>
          <path d="M3 5V19A9 3 0 0 0 21 19V5">
          </path>
          <path d="M3 12A9 3 0 0 0 21 12">
          </path>
         </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">
         AI-Ready Format
        </h3>
        <p class="text-muted-foreground">
         Structured JSON output perfect for n8n workflows and AI applications.
        </p>
       </div>
       <div class="p-6 rounded-xl bg-card border border-border hover:border-primary/50 transition-all duration-300 hover:-translate-y-1">
        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center mb-4">
         <svg class="lucide lucide-refresh-cw w-6 h-6 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8">
          </path>
          <path d="M21 3v5h-5">
          </path>
          <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16">
          </path>
          <path d="M8 16H3v5">
          </path>
         </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">
         Lifetime Updates
        </h3>
        <p class="text-muted-foreground">
         Free updates and improvements to all APIs included with purchase.
        </p>
       </div>
       <div class="p-6 rounded-xl bg-card border border-border hover:border-primary/50 transition-all duration-300 hover:-translate-y-1">
        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center mb-4">
         <svg class="lucide lucide-headphones w-6 h-6 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3">
          </path>
         </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">
         Installation Support
        </h3>
        <p class="text-muted-foreground">
         Complete installation assistance included. We'll help get you up and running.
        </p>
       </div>
       <div class="p-6 rounded-xl bg-card border border-border hover:border-primary/50 transition-all duration-300 hover:-translate-y-1">
        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center mb-4">
         <svg class="lucide lucide-rocket w-6 h-6 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z">
          </path>
          <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z">
          </path>
          <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0">
          </path>
          <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5">
          </path>
         </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">
         Quick Setup
        </h3>
        <p class="text-muted-foreground">
         Get started in minutes with our guided installation process and documentation.
        </p>
       </div>
       <div class="p-6 rounded-xl bg-card border border-border hover:border-primary/50 transition-all duration-300 hover:-translate-y-1">
        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center mb-4">
         <svg class="lucide lucide-lock-open w-6 h-6 text-primary" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <rect height="11" rx="2" ry="2" width="18" x="3" y="11">
          </rect>
          <path d="M7 11V7a5 5 0 0 1 9.9-1">
          </path>
         </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">
         Data Independence
        </h3>
        <p class="text-muted-foreground">
         Complete data ownership with no vendor lock-in. Your infrastructure, your control.
        </p>
       </div>
      </div>
     </div>
    </section>
    <section class="py-24 relative" id="api">
     <div class="absolute inset-0 bg-gradient-to-b from-background via-secondary/10 to-background">
     </div>
     <div class="container mx-auto px-4 relative z-10">
      <div class="text-center mb-16">
       <h2 class="text-4xl md:text-5xl font-bold mb-4">
        API
        <span class="gradient-text">
         Examples
        </span>
       </h2>
       <p class="text-xl text-muted-foreground max-w-2xl mx-auto">
        Clean JSON responses that integrate seamlessly with any platform. Perfect for n8n workflows, custom bots, and AI applications.
       </p>
      </div>
      <div class="space-y-32">
       <div class="grid lg:grid-cols-2 gap-12 items-center mb-32">
        <div>
         <h3 class="text-3xl font-bold mb-4">
          Market Data API
         </h3>
         <p class="text-lg text-muted-foreground mb-6">
          Get real-time and historical market data from MT5 in JSON format.
         </p>
         <div class="space-y-3">
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Live tick data streaming
           </span>
          </div>
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Historical OHLC data
           </span>
          </div>
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Multiple timeframes
           </span>
          </div>
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Symbol information
           </span>
          </div>
         </div>
        </div>
        <div class="relative">
         <div class="absolute -inset-4 bg-gradient-to-r from-primary/20 to-[hsl(199,89%,48%)]/20 rounded-2xl blur-xl">
         </div>
         <div class="relative code-block text-foreground/90">
          <div class="flex items-center gap-2 mb-4 pb-4 border-b border-border">
           <div class="w-3 h-3 rounded-full bg-destructive/60">
           </div>
           <div class="w-3 h-3 rounded-full bg-warning/60">
           </div>
           <div class="w-3 h-3 rounded-full bg-success/60">
           </div>
           <span class="ml-4 text-xs text-muted-foreground">
            market-data.js
           </span>
          </div>
          <pre class="overflow-x-auto"><code class="text-sm leading-relaxed"><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">1</span><span class="text-muted-foreground">// Get real-time market data</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">2</span><span class="text-[hsl(199,89%,48%)]">const response = await fetch(</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">3</span><span class="text-muted-foreground">  'https://your-server.com/api/v1/market/EURUSD/tick'</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">4</span><span class="text-foreground/90">);</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">5</span><span class="text-[hsl(199,89%,48%)]">const data = await response.json();</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">6</span><span class="text-foreground/90"></span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">7</span><span class="text-muted-foreground">// Response</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">8</span><span class="text-foreground/90">{</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">9</span><span class="text-primary">  "symbol": "EURUSD",</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">10</span><span class="text-primary">  "bid": 1.08542,</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">11</span><span class="text-primary">  "ask": 1.08544,</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">12</span><span class="text-primary">  "time": "2024-01-15T14:32:45.123Z",</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">13</span><span class="text-primary">  "spread": 0.00002</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">14</span><span class="text-foreground/90">}</span></div></code></pre>
         </div>
        </div>
       </div>
       <div class="grid lg:grid-cols-2 gap-12 items-center mb-32">
        <div class="lg:order-2">
         <h3 class="text-3xl font-bold mb-4">
          Economic Calendar API
         </h3>
         <p class="text-lg text-muted-foreground mb-6">
          Access comprehensive economic events and news data for informed trading decisions.
         </p>
         <div class="space-y-3">
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Global economic events
           </span>
          </div>
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Impact ratings
           </span>
          </div>
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Historical data access
           </span>
          </div>
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Real-time updates
           </span>
          </div>
         </div>
        </div>
        <div class="relative lg:order-1">
         <div class="absolute -inset-4 bg-gradient-to-r from-primary/20 to-[hsl(199,89%,48%)]/20 rounded-2xl blur-xl">
         </div>
         <div class="relative code-block text-foreground/90">
          <div class="flex items-center gap-2 mb-4 pb-4 border-b border-border">
           <div class="w-3 h-3 rounded-full bg-destructive/60">
           </div>
           <div class="w-3 h-3 rounded-full bg-warning/60">
           </div>
           <div class="w-3 h-3 rounded-full bg-success/60">
           </div>
           <span class="ml-4 text-xs text-muted-foreground">
            calendar.js
           </span>
          </div>
          <pre class="overflow-x-auto"><code class="text-sm leading-relaxed"><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">1</span><span class="text-muted-foreground">// Get upcoming economic events</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">2</span><span class="text-[hsl(199,89%,48%)]">const response = await fetch(</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">3</span><span class="text-muted-foreground">  'https://your-server.com/api/v1/calendar?impact=high'</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">4</span><span class="text-foreground/90">);</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">5</span><span class="text-[hsl(199,89%,48%)]">const events = await response.json();</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">6</span><span class="text-foreground/90"></span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">7</span><span class="text-muted-foreground">// Response</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">8</span><span class="text-foreground/90">{</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">9</span><span class="text-primary">  "events": [</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">10</span><span class="text-primary">    {</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">11</span><span class="text-primary">      "title": "FOMC Meeting",</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">12</span><span class="text-primary">      "country": "USD",</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">13</span><span class="text-primary">      "impact": "high",</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">14</span><span class="text-primary">      "time": "2024-01-15T18:00:00Z"</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">15</span><span class="text-primary">    }</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">16</span><span class="text-primary">  ]</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">17</span><span class="text-foreground/90">}</span></div></code></pre>
         </div>
        </div>
       </div>
       <div class="grid lg:grid-cols-2 gap-12 items-center mb-32">
        <div>
         <h3 class="text-3xl font-bold mb-4">
          Orders API
         </h3>
         <p class="text-lg text-muted-foreground mb-6">
          Full MT5 trading capabilities exposed via REST endpoints for complete trade automation.
         </p>
         <div class="space-y-3">
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Open &amp; close positions
           </span>
          </div>
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Modify orders
           </span>
          </div>
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Account information
           </span>
          </div>
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Trade history
           </span>
          </div>
         </div>
        </div>
        <div class="relative">
         <div class="absolute -inset-4 bg-gradient-to-r from-primary/20 to-[hsl(199,89%,48%)]/20 rounded-2xl blur-xl">
         </div>
         <div class="relative code-block text-foreground/90">
          <div class="flex items-center gap-2 mb-4 pb-4 border-b border-border">
           <div class="w-3 h-3 rounded-full bg-destructive/60">
           </div>
           <div class="w-3 h-3 rounded-full bg-warning/60">
           </div>
           <div class="w-3 h-3 rounded-full bg-success/60">
           </div>
           <span class="ml-4 text-xs text-muted-foreground">
            orders.js
           </span>
          </div>
          <pre class="overflow-x-auto"><code class="text-sm leading-relaxed"><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">1</span><span class="text-muted-foreground">// Open a new position</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">2</span><span class="text-[hsl(199,89%,48%)]">const response = await fetch(</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">3</span><span class="text-muted-foreground">  'https://your-server.com/api/v1/orders', {</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">4</span><span class="text-muted-foreground">    method: 'POST',</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">5</span><span class="text-muted-foreground">    body: JSON.stringify({</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">6</span><span class="text-primary">      symbol: 'EURUSD',</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">7</span><span class="text-primary">      type: 'buy',</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">8</span><span class="text-primary">      volume: 0.1,</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">9</span><span class="text-primary">      stopLoss: 1.0800,</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">10</span><span class="text-primary">      takeProfit: 1.0900</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">11</span><span class="text-muted-foreground">    })</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">12</span><span class="text-muted-foreground">  });</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">13</span><span class="text-[hsl(199,89%,48%)]">const order = await response.json();</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">14</span><span class="text-foreground/90"></span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">15</span><span class="text-muted-foreground">// Response: { "orderId": 12345, "status": "opened" }</span></div></code></pre>
         </div>
        </div>
       </div>
       <div class="grid lg:grid-cols-2 gap-12 items-center mb-32">
        <div class="lg:order-2">
         <h3 class="text-3xl font-bold mb-4">
          Chart Image API
         </h3>
         <p class="text-lg text-muted-foreground mb-6">
          Generate chart images on-demand for reports, analysis, and visualization needs.
         </p>
         <div class="space-y-3">
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Custom timeframes
           </span>
          </div>
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Indicator overlays
           </span>
          </div>
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            Multiple styles
           </span>
          </div>
          <div class="flex items-center gap-3">
           <div class="w-2 h-2 rounded-full bg-primary">
           </div>
           <span class="text-muted-foreground">
            High-resolution export
           </span>
          </div>
         </div>
        </div>
        <div class="relative lg:order-1">
         <div class="absolute -inset-4 bg-gradient-to-r from-primary/20 to-[hsl(199,89%,48%)]/20 rounded-2xl blur-xl">
         </div>
         <div class="relative code-block text-foreground/90">
          <div class="flex items-center gap-2 mb-4 pb-4 border-b border-border">
           <div class="w-3 h-3 rounded-full bg-destructive/60">
           </div>
           <div class="w-3 h-3 rounded-full bg-warning/60">
           </div>
           <div class="w-3 h-3 rounded-full bg-success/60">
           </div>
           <span class="ml-4 text-xs text-muted-foreground">
            charts.js
           </span>
          </div>
          <pre class="overflow-x-auto"><code class="text-sm leading-relaxed"><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">1</span><span class="text-muted-foreground">// Generate chart image</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">2</span><span class="text-[hsl(199,89%,48%)]">const response = await fetch(</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">3</span><span class="text-muted-foreground">  'https://your-server.com/api/v1/chart?' +</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">4</span><span class="text-muted-foreground">  'symbol=EURUSD&amp;timeframe=H1&amp;' +</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">5</span><span class="text-muted-foreground">  'width=800&amp;height=400'</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">6</span><span class="text-foreground/90">);</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">7</span><span class="text-[hsl(199,89%,48%)]">const blob = await response.blob();</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">8</span><span class="text-[hsl(199,89%,48%)]">const imageUrl = URL.createObjectURL(blob);</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">9</span><span class="text-foreground/90"></span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">10</span><span class="text-muted-foreground">// Response: PNG image data</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">11</span><span class="text-foreground/90">{</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">12</span><span class="text-primary">  "contentType": "image/png",</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">13</span><span class="text-primary">  "size": 45678,</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">14</span><span class="text-primary">  "timestamp": "2024-01-15T14:32:45Z"</span></div><div class="hover:bg-secondary/30 px-2 -mx-2 rounded"><span class="text-muted-foreground/50 select-none w-8 inline-block">15</span><span class="text-foreground/90">}</span></div></code></pre>
         </div>
        </div>
       </div>
      </div>
     </div>
    </section>
    <section class="py-24 relative overflow-hidden" id="pricing">
     <div class="absolute bottom-0 left-1/4 w-96 h-96 bg-primary/10 rounded-full blur-[128px]">
     </div>
     <div class="container mx-auto px-4 relative z-10">
      <div class="text-center mb-16">
       <h2 class="text-4xl md:text-5xl font-bold mb-4">
        Simple,
        <span class="gradient-text">
         Transparent
        </span>
        Pricing
       </h2>
       <p class="text-xl text-muted-foreground max-w-2xl mx-auto">
        One-time payment with installation included. Self-hosted. No recurring fees.
       </p>
      </div>
      <div class="grid lg:grid-cols-2 gap-8 max-w-5xl mx-auto">
       <div class="p-8 rounded-2xl bg-card border border-border">
        <h3 class="text-2xl font-semibold mb-6">
         Individual APIs
        </h3>
        <p class="text-muted-foreground mb-8">
         Purchase only what you need
         <div class="space-y-4 mb-8">
          <div class="flex items-center justify-between p-4 rounded-lg bg-secondary/50">
           <span>
            Market Data API
           </span>
           <span class="font-semibold">
            $699
           </span>
          </div>
          <div class="flex items-center justify-between p-4 rounded-lg bg-secondary/50">
           <span>
            Economic Calendar API
           </span>
           <span class="font-semibold">
            $699
           </span>
          </div>
          <div class="flex items-center justify-between p-4 rounded-lg bg-secondary/50">
           <span>
            Orders API
           </span>
           <span class="font-semibold">
            $899
           </span>
          </div>
          <div class="flex items-center justify-between p-4 rounded-lg bg-secondary/50">
           <span>
            Chart Image API
           </span>
           <span class="font-semibold">
            $499
           </span>
          </div>
         </div>
         <div class="text-center pt-6 border-t border-border">
          <span class="text-muted-foreground">
           Total if purchased separately:
          </span>
          <div class="text-3xl font-bold mt-2">
           $2 796
          </div>
         </div>
        </p>
       </div>
       <div class="relative p-8 rounded-2xl gradient-border glow">
        <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-gradient-to-r from-primary to-[hsl(199,89%,48%)] text-primary-foreground text-sm font-medium flex items-center gap-1">
         <svg class="lucide lucide-star w-4 h-4" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z">
          </path>
         </svg>
         Best Value
        </div>
        <h3 class="text-2xl font-semibold mb-2 mt-2">
         Complete Package
        </h3>
        <p class="text-muted-foreground mb-8">
         All 4 APIs in one bundle
         <div class="mb-8">
          <div class="text-5xl font-bold gradient-text mb-2">
           $1 999
          </div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-success/10 text-success text-sm">
           Save $797 (Save 29%)
          </div>
         </div>
         <ul class="space-y-3 mb-8">
          <li class="flex items-center gap-3">
           <svg class="lucide lucide-check w-5 h-5 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 6 9 17l-5-5">
            </path>
           </svg>
           <span>
            Market Data API
           </span>
           <li class="flex items-center gap-3">
            <svg class="lucide lucide-check w-5 h-5 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
             <path d="M20 6 9 17l-5-5">
             </path>
            </svg>
            <span>
             Economic Calendar API
            </span>
            <li class="flex items-center gap-3">
             <svg class="lucide lucide-check w-5 h-5 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 6 9 17l-5-5">
              </path>
             </svg>
             <span>
              Orders API
             </span>
             <li class="flex items-center gap-3">
              <svg class="lucide lucide-check w-5 h-5 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
               <path d="M20 6 9 17l-5-5">
               </path>
              </svg>
              <span>
               Chart Image API
              </span>
              <li class="flex items-center gap-3">
               <svg class="lucide lucide-check w-5 h-5 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 6 9 17l-5-5">
                </path>
               </svg>
               <span>
                Full installation support
               </span>
               <li class="flex items-center gap-3">
                <svg class="lucide lucide-check w-5 h-5 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                 <path d="M20 6 9 17l-5-5">
                 </path>
                </svg>
                <span>
                 Lifetime updates
                </span>
                <li class="flex items-center gap-3">
                 <svg class="lucide lucide-check w-5 h-5 text-primary flex-shrink-0" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 6 9 17l-5-5">
                  </path>
                 </svg>
                 <span>
                  Priority support
                 </span>
                </li>
               </li>
              </li>
             </li>
            </li>
           </li>
          </li>
         </ul>
         <a href="https://t.me/arrissa_ai" target="_blank" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg ring-offset-background transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 bg-gradient-to-r from-primary to-[hsl(199,89%,48%)] text-primary-foreground font-semibold shadow-xl shadow-primary/30 hover:shadow-primary/50 hover:scale-105 h-14 px-10 text-lg w-full">
          Get Complete Package
          <svg class="lucide lucide-arrow-right w-5 h-5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
           <path d="M5 12h14">
           </path>
           <path d="m12 5 7 7-7 7">
           </path>
          </svg>
         </a>
        </p>
       </div>
      </div>
     </div>
    </section>
    <section class="py-24 relative overflow-hidden">
     <div class="absolute inset-0 bg-gradient-to-br from-primary/10 via-background to-[hsl(199,89%,48%)]/10">
     </div>
     <div class="absolute inset-0 grid-pattern opacity-30">
     </div>
     <div class="container mx-auto px-4 relative z-10">
      <div class="max-w-3xl mx-auto text-center">
       <h2 class="text-4xl md:text-5xl font-bold mb-6">
        Ready to
        <span class="gradient-text">
         Get Started?
        </span>
       </h2>
       <p class="text-xl text-muted-foreground mb-10">
        Join developers and traders who have already transformed their MT5 data into powerful REST APIs.
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
         <a href="https://t.me/arrissa_ai" target="_blank" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg ring-offset-background transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 bg-gradient-to-r from-primary to-[hsl(199,89%,48%)] text-primary-foreground font-semibold shadow-xl shadow-primary/30 hover:shadow-primary/50 hover:scale-105 h-14 px-10 text-lg">
          Get Started Now
          <svg class="lucide lucide-arrow-right w-5 h-5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
           <path d="M5 12h14">
           </path>
           <path d="m12 5 7 7-7 7">
           </path>
          </svg>
         </a>
         <a href="https://t.me/arrissa_ai" target="_blank" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg font-medium ring-offset-background transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border-2 border-primary/50 bg-transparent text-foreground hover:bg-primary/10 hover:border-primary h-14 px-10 text-lg">
          <svg class="lucide lucide-message-circle w-5 h-5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
           <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z">
           </path>
          </svg>
          Contact Sales
         </a>
        </div>
        <p class="text-sm text-muted-foreground mt-8">
         Have questions? Our team is ready to help you find the right solution.
        </p>
       </p>
      </div>
     </div>
    </section>
    <footer class="py-12 border-t border-border">
     <div class="container mx-auto px-4">
      <div class="flex flex-col md:flex-row items-center justify-between gap-8">
       <div class="flex items-center gap-2">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary to-[hsl(199,89%,48%)] flex items-center justify-center">
         <svg class="lucide lucide-database w-5 h-5 text-primary-foreground" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
          <ellipse cx="12" cy="5" rx="9" ry="3">
          </ellipse>
          <path d="M3 5V19A9 3 0 0 0 21 19V5">
          </path>
          <path d="M3 12A9 3 0 0 0 21 12">
          </path>
         </svg>
        </div>
        <span class="text-xl font-bold tracking-tight">
         Arrissa
         <span class="gradient-text">
          Data
         </span>
        </span>
       </div>
       <div class="flex items-center gap-8 text-sm text-muted-foreground">
        <a class="hover:text-foreground transition-colors" href="#products">
         Products
        </a>
        <a class="hover:text-foreground transition-colors" href="#features">
         Features
        </a>
        <a class="hover:text-foreground transition-colors" href="#pricing">
         Pricing
        </a>
        <a class="hover:text-foreground transition-colors" href="#api">
         API Docs
        </a>
       </div>
       <div class="text-sm text-muted-foreground">
        © 2024 Arrissa Data. All rights reserved.
       </div>
      </div>
     </div>
    </footer>
   </main>
  </div>
 <script>
document.addEventListener('DOMContentLoaded', function() {
  console.log('Mobile menu script loaded');
  var toggleBtn = document.getElementById('mobile-menu-toggle');
  var closeBtn = document.getElementById('mobile-menu-close');
  var menu = document.getElementById('mobile-menu');
  var overlay = document.getElementById('mobile-menu-overlay');
  
  console.log('Toggle button:', toggleBtn);
  console.log('Menu:', menu);
  console.log('Overlay:', overlay);
  
  function openMenu() {
    console.log('Opening menu');
    if (menu && overlay) {
      menu.classList.add('open');
      overlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  }
  
  function closeMenu() {
    console.log('Closing menu');
    if (menu && overlay) {
      menu.classList.remove('open');
      overlay.classList.remove('active');
      document.body.style.overflow = '';
    }
  }
  
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function(e) {
      console.log('Toggle clicked');
      openMenu();
    });
  }
  
  if (closeBtn) {
    closeBtn.addEventListener('click', function(e) {
      console.log('Close clicked');
      closeMenu();
    });
  }
  
  if (overlay) {
    overlay.addEventListener('click', closeMenu);
  }
  
  // Close mobile menu when clicking a link
  var menuLinks = document.querySelectorAll('.mobile-menu-link');
  menuLinks.forEach(function(link) {
    link.addEventListener('click', closeMenu);
  });
  
  // Load saved theme
  var savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'light') {
    document.body.classList.add('light-theme');
    updateThemeIcon(true);
  }
});

function toggleTheme() {
  var body = document.body;
  var isLight = body.classList.contains('light-theme');
  
  if (isLight) {
    body.classList.remove('light-theme');
    localStorage.setItem('theme', 'dark');
    updateThemeIcon(false);
  } else {
    body.classList.add('light-theme');
    localStorage.setItem('theme', 'light');
    updateThemeIcon(true);
  }
}

function updateThemeIcon(isLight) {
  var themeIcon = document.getElementById('theme-icon');
  
  if (isLight) {
    // Sun icon for light mode
    themeIcon.innerHTML = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
  } else {
    // Moon icon for dark mode
    themeIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
  }
}
</script>
</body>
</html>
