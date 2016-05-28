# Chemistry-LatticeEnthalpy
This is an interactive web application that uses the Born-Haber cycle and Hess's Law to calculate and teach students about lattice enthalpies.

It can be seen in action at: https://barancode.com/projects/lattice/

The app makes use of Chemical information from Wolfram Alpha and other miscellanious sources.

The app has been created in PHP. The PHP runtime should be thread-safe and should have the pthreads extension enabled to run this, because multiple threads are used when fetching the data from Wolfram Alpha.

Some of the compounds and values have been hard-coded into the program due to Wolfram Alpha's incomplete database.

This program fully supports the following compounds:
LiF, LiCl, LiBr, LiI, NaF, NaCl, NaBr, NaI, KF, KCl, KBr, KI, RbF, RbCl, RbBr, RbI, CsF, CsCl, CsBr, CsI, CaF2, BeCl2, MgCl2, CaCl2, SrCl2, BaCl2, MgO, CaO, SrO, BaO, CuCl2, AgF, AgCl, AgBr, AgI, FeBr3
and more.